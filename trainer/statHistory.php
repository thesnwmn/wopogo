<?php

require_once dirname(__FILE__).'/../app/web.php';
require_once dirname(__FILE__).'/../app/lib/stats.php';
require_once dirname(__FILE__).'/../app/lib/trainers.php';

$trainer = NULL;
$stat = NULL;

try {
  if (!isset($_GET['trainer']) || !isset($_GET['stat'])) {
    webBadRequest("Must supply 'trainer' and 'stat'");
  }
  $statId = $_GET['stat'];
  $trainer = GetTrainer($_GET['trainer'], false);
  $stats = GetStats(true);
  $history = GetStatHistory($trainer['id'], $_GET['stat']);
} catch (Exception $e) {
  webException($e);
}

?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<?php if (!array_key_exists($statId, $stats['stats'])) { ?>

  <p>Unrecognised stat</p>

<?php } else { ?>

<div class="row">
  <h1><?php echo $trainer['name']; ?>:<br>
      <?php echo $stats['stats'][$statId]['title']; ?></h1>
</div>

<div class="row">
  <h3>Plot</h3>
</div>

<div class="row">
  <canvas id="theChart" height="150vmin"></canvas>
</div>

<?php if ($trainer['flags']['editable'] === true) { ?>

<div class="row">
  <h3>Add Data</h3>
</div>

<form onSubmit="return AddData()">

<?php require_once dirname(__FILE__).'/../res/include/timestamp.php'; ?>

<div class="row">
  <div class="twelve columns">
    <label for="value">Value</label>
    <input id="value" type="text" class="u-full-width" name="value">
  </div>
</div>

<div class="row">
  <button class="button-primary">Add</button>
  <span class="js-form-result block error"></span>
</div>

</form>

<?php } ?>

<div class="row">
  <h3>Historic Data</h3>
</div>

<div class="row">
  <table class="u-full-width">
    <thead>
      <tr>
        <th width="50%">Date</th>
        <th width="50%">Value</th>
        <?php if ($trainer['flags']['editable'] === true) { ?> <th></th> <?php } ?>
      </tr>
    </thead>
    <tbody id="data-body">
    </tbody>
  </table>
</div>

<?php } ?>

<script>

function button(icon, onclick) {
  var btn = document.createElement("a");
  btn.href = "";
  btn.className = "material-icons";
  btn.textContent = icon;
  btn.onclick = function() {
    onclick();
    return false;
  };
  return btn;
}

function getActionsCell(row) {
  return row.querySelector(".js-actions");
}

function getValueCell(row) {
  return row.querySelector(".js-value");
}

function resetRow(row) {
  // Change value
  var valueCell = getValueCell(row);
  valueCell.innerHTML = "";
  valueCell.textContent = numberWithCommas(valueCell.getAttribute("data-value"));
  // Change buttons
  var actions = getActionsCell(row);
  if (actions !== null) {
    actions.innerHTML = "";
    actions.appendChild(button("create", function() { editRow(row); }));
    actions.appendChild(button("delete", function() { deleteRow(row); }));
  }
}

function editRow(row) {
  // Change value
  var valueCell = getValueCell(row);
  var input = document.createElement("input");
  input.type = "text";
  input.value = valueCell.getAttribute("data-value");
  valueCell.innerHTML = "";
  valueCell.appendChild(input);
  // Change buttons
  var actions = getActionsCell(row);
  actions.innerHTML = "";
  actions.appendChild(button("done", function() { saveEdit(row); }));
  actions.appendChild(button("clear", function() { resetRow(row); }));
}

function deleteRow(row) {
  // Change value
  var valueCell = getValueCell(row);
  valueCell.innerHTML = "";
  valueCell.textContent = "Delete?";
  // Change buttons
  var actions = getActionsCell(row);
  actions.innerHTML = "";
  actions.appendChild(button("done", function() { doDelete(row); }));
  actions.appendChild(button("clear", function() { resetRow(row); }));
}

function errorRow(row, errorText) {
  // Change value
  var valueCell = getValueCell(row);
  valueCell.innerHTML = "";
  var errorSpan = document.createElement("span");
  errorSpan.textContent = errorText;
  errorSpan.className = "error";
  valueCell.appendChild(errorSpan);
  // Change buttons
  var actions = getActionsCell(row);
  actions.innerHTML = "";
  actions.appendChild(button("clear", function() { resetRow(row); }));
}

function saveEdit(row) {

  var value = row.querySelector("input").value;

  if (value === "") {

    errorRow(row, "No value entered");

  } else {

    var data = { stats: [ {
      trainer: "<?= $_GET['trainer'] ?>",
      stat: "<?= $_GET['stat'] ?>",
      timestamp: function() {
        return row.querySelector(".js-timestamp").getAttribute("data-value");
      }(),
      value: value
    } ] };

    httpPatchAsync(
      "<?=$site_root?>/app/api/v1/stat/stats.php",
      JSON.stringify(data),
      function() {
        getValueCell(row).setAttribute("data-value", value);
        resetRow(row);
        updatePlot();
      },
      function(response) {
        errorRow(row, getErrorText(response, "Failed: Reason unknown"));
      });
  }
}

function doDelete(row) {

  var data = { stats: [ {
    trainer: "<?= $_GET['trainer'] ?>",
    stat: "<?= $_GET['stat'] ?>",
    timestamp: function() {
      return row.querySelector(".js-timestamp").getAttribute("data-value");
    }()
  } ] };

  httpDeleteAsync(
    "<?=$site_root?>/app/api/v1/stat/stats.php",
    JSON.stringify(data),
    function() {
      row.parentNode.removeChild(row);
      updatePlot();
    },
    function(response) {
      errorRow(row, getErrorText(response, "Failed: Reason unknown"));
    });
}

function getErrorText(response, defaultText) {
  var errorText;
  try {
    errorText = response.errors[0].text;
  } catch (e) {}
  if (errorText === null || typeof errorText === "undefined") {
     errorText = defaultText;
  }
  return errorText;
}

function updatePlot() {
  var data = [];
  document.querySelectorAll(".js-data-row").forEach(function(row) {
    data.push({
      x: row.querySelector(".js-timestamp").getAttribute("data-value"),
      y: row.querySelector(".js-value").getAttribute("data-value")
    });
  });
  plotData(data);
}

function plotData(data) {
  chart.config.data.datasets[0].data = data;
  chart.update();
}

var chart = null;

function refreshData() {

  var dataBody = document.querySelector("#data-body");
  dataBody.innerHTML = "";

  httpGetAsync(
    "<?=$site_root?>/app/api/v1/trainer/statHistory.php?trainer=<?=$_GET['trainer']?>&stat=<?=$_GET['stat']?>",
    function(response) {

      var data = [];

      response.entries.forEach(function(entry) {

        var row = document.createElement("tr");
        row.className = "js-data-row";

        var timestampTd = document.createElement("td");
        timestampTd.className = "js-timestamp";
        timestampTd.setAttribute("data-value", entry.timestamp);
        timestampTd.textContent = displayDatetime(entry.timestamp);

        var valueTd = document.createElement("td");
        valueTd.className = "js-value";
        valueTd.setAttribute("data-value", entry.value);
        valueTd.textContent = numberWithCommas(entry.value);

        row.appendChild(timestampTd);
        row.appendChild(valueTd);

        <?php if ($trainer['flags']['editable'] === true) { ?>
          var actionsTd = document.createElement("td");
          actionsTd.className = "js-actions nowrap";
          row.appendChild(actionsTd);
        <?php } ?>

        dataBody.appendChild(row);

        resetRow(row);

        data.push({
          x: row.querySelector(".js-timestamp").getAttribute("data-value"),
          y: row.querySelector(".js-value").getAttribute("data-value")
        });
      });

      plotData(data);
    },
    function(response) {
      dataBody.innerHTML = "<tr><td colspan=3 class='error'>Failed to retrieve data</td></tr>";
    }
  );
}

window.onload = function() {

  var ctx = document.getElementById("theChart").getContext('2d');
  chart = new Chart(ctx, {
        type: 'line',
        data: {
          datasets: [{
            data: []
          }]
        },
        options: {
          elements: {
            line: {
              tension: 0, // disables bezier curves
            }
          },
          legend: {
            display: false
          },
          scales: {
            yAxes: [{
              scaleLabel: {
                display: true,
                labelString: "<?php echo $stats['stats'][$statId]['data_unit'] ?>"
              }
            }],
            xAxes: [{
              type: "time",
              distribution: 'linear',
              ticks: {
                autoSkip: true,
                maxTicksLimit: 25
              },
              time: {
                unit: "day"
              }
            }]
          }
        }
    });

    refreshData();
}

<?php if ($trainer['flags']['editable'] === true) { ?>

function AddData() {

  var errorEle = document.querySelector(".js-form-result");
  errorEle.innerHTML = "";

  var error = null;
  var timestamp = null;
  var value = null;

  if (error === null) {
    try {
      timestamp = datetime.getTimestamp();
    } catch (err) {
      error = err;
    }
  }

  if (error === null) {
    value = document.querySelector("#value").value;
    if (value === null || value === "") {
      error = "No value";
    }
  }

  if (error !== null) {
    errorEle.textContent = error;
  } else {
      httpPostAsync(
        "<?=$site_root?>/app/api/v1/stat/stats.php",
        JSON.stringify({ stats: [{
          trainer: "<?= $_GET['trainer'] ?>",
          stat: "<?= $_GET['stat'] ?>",
          timestamp: timestamp,
          value: value
        }]}),
        function() {
          refreshData();
        },
        function(response) { errorEle.textContent = response.errors[0].text; }
      );
  }

  return false;
}

var datetime = initaliseDateTime();

<?php } ?>

</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
