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

<div class="row">
  <div class="eight columns">
    <label>Date (blank for today)</label>
    <input type="date" id="date" class="u-full-width native-timestamp" name="timestamp">
    <div class="fallback-timestamp">
        <select id="day" name="day"></select>
        <select id="month" name="month">
          <option value="" selected>month</option>
          <option value="01">January</option>
          <option value="02">February</option>
          <option value="03">March</option>
          <option value="04">April</option>
          <option value="05">May</option>
          <option value="06">June</option>
          <option value="07">July</option>
          <option value="08">August</option>
          <option value="09">September</option>
          <option value="10">October</option>
          <option value="11">November</option>
          <option value="12">December</option>
        </select>
        <select id="year" name="year"></select>
    </div>
  </div>
  <div class="four columns">
    <label>Time (optional)</label>
    <div class="nowrap">
      <select id="hour" name="hour" placeholder="hh"></select> :
      <select id="minute" name="minute"></select>
    </div>
  </div>
</div>

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
  var date = "";
  var timestamp = null;

  if (timestampFallback) {

    var day = daySelect.value;
    var month = monthSelect.value;
    var year = yearSelect.value;

    if (day === "" && month === "" && year === "") {
      // Nothing, defaulted later
    } else if (day === "" || month === "" || year === "") {
      error = "Incomplete date";
    } else {
      date = year + "-" + month + "-" + day;
    }

  } else {

    date = datePicker.value;
  }

  var time = "";

  if (error === null && date === "") {
    var d = new Date();
    date = d.getFullYear() + "/" +
           ("0" + (d.getMonth()+1)).slice(-2) + "/" +
           ("0" + d.getDate()).slice(-2);
    if (hourSelect.value === "" && minuteSelect.value === "") {
      // No time and no date, select now
      time = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
    } else {
      error = "Time not allowed without date";
    }
  } else {
    date = date.replace(/-/g, '/');
  }

  if (error === null && time === "") {
    if (hourSelect.value === "" && minuteSelect.value === "") {
      var d = new Date("2000/05/01 00:00");
      time = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getUTCMinutes()).slice(-2);
    } else if (hourSelect.value === "" || minuteSelect.value === "") {
      error = "Incomplete time";
    } else {
      time = hourSelect.value + ":" + minuteSelect.value;
    }
  }

  if (error === null) {
    var d = new Date(date + " " + time);
    timestamp = d.getUTCFullYear() + "-" +
             ("0" + (d.getUTCMonth()+1)).slice(-2) + "-" +
             ("0" + d.getUTCDate()).slice(-2) + " " +
             ("0" + d.getUTCHours()).slice(-2) + ":" +
             ("0" + d.getUTCMinutes()).slice(-2);
  }

  var value = null;
  if (error === null) {
    value = document.querySelector("#value").value;
    if (value === null || value === "") {
      error = "No value";
    }
  }

  if (error === null) {
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

  if (error !== null) {
    errorEle.textContent = error;
  }

  return false;
}

/* Date time picker
 */

var nativePicker = document.querySelector('.native-timestamp');
var fallbackPicker = document.querySelector('.fallback-timestamp');

var datePicker = document.querySelector('#date');
var yearSelect = document.querySelector('#year');
var monthSelect = document.querySelector('#month');
var daySelect = document.querySelector('#day');
var hourSelect = document.querySelector('#hour');
var minuteSelect = document.querySelector('#minute');

// hide fallback initially
fallbackPicker.style.display = 'none';

var timestampFallback = false;
// test whether a new datetime-local input falls back to a text input or not
var test = document.createElement('input');
test.type = 'datetime-local';
// if it does, run the code inside the if() {} block
if(test.type === 'text') {

  timestampFallback = true;

  // hide the native picker and show the fallback
  nativePicker.style.display = 'none';
  fallbackPicker.style.display = 'block';

  // populate the days and years dynamically
  // (the months are always the same, therefore hardcoded)
  populateDays(monthSelect.value);
  populateYears();
}
populateHours();
populateMinutes();

function populateDays(month) {
  // delete the current set of <option> elements out of the
  // day <select>, ready for the next set to be injected
  while(daySelect.firstChild){
    daySelect.removeChild(daySelect.firstChild);
  }

  var option = document.createElement('option');
  option.textContent = "day";
  option.value = "";
  option.selected = true;
  daySelect.appendChild(option);

  // Create variable to hold new number of days to inject
  var dayNum;

  // 31 or 30 days?
  if(month === 'January' | month === 'March' | month === 'May' | month === 'July' | month === 'August' | month === 'October' | month === 'December') {
    dayNum = 31;
  } else if(month === 'April' | month === 'June' | month === 'September' | month === 'November') {
    dayNum = 30;
  } else {
  // If month is February, calculate whether it is a leap year or not
    var year = yearSelect.value;
    (year - 2016) % 4 === 0 ? dayNum = 29 : dayNum = 28;
  }

  // inject the right number of new <option> elements into the day <select>
  for(i = 1; i <= dayNum; i++) {
    var option = document.createElement('option');
    option.textContent = (i < 10) ? ("0" + i) : i;
    daySelect.appendChild(option);
  }

  // if previous day has already been set, set daySelect's value
  // to that day, to avoid the day jumping back to 1 when you
  // change the year
  if(previousDay) {
    daySelect.value = previousDay;

    // If the previous day was set to a high number, say 31, and then
    // you chose a month with less total days in it (e.g. February),
    // this part of the code ensures that the highest day available
    // is selected, rather than showing a blank daySelect
    if(daySelect.value === "") {
      daySelect.value = previousDay - 1;
    }

    if(daySelect.value === "") {
      daySelect.value = previousDay - 2;
    }

    if(daySelect.value === "") {
      daySelect.value = previousDay - 3;
    }
  }
}

function populateYears() {
  // get this year as a number
  var date = new Date();
  var year = date.getFullYear();

  var option = document.createElement('option');
  option.textContent = "year";
  option.value = "";
  option.selected = true;
  yearSelect.appendChild(option);

  // Make this year, and the 100 years before it available in the year <select>
  for(var i = 0; year-i >= 2016; i++) {
    var option = document.createElement('option');
    option.textContent = year-i;
    yearSelect.appendChild(option);
  }
}

function populateHours() {

  var option = document.createElement('option');
  option.textContent = "h";
  option.value = "";
  option.selected = true;
  hourSelect.appendChild(option);

  // populate the hours <select> with the 24 hours of the day
  for(var i = 0; i <= 23; i++) {
    var option = document.createElement('option');
    option.textContent = (i < 10) ? ("0" + i) : i;
    hourSelect.appendChild(option);
  }
}

function populateMinutes() {

  var option = document.createElement('option');
  option.textContent = "m";
  option.value = "";
  option.selected = true;
  minuteSelect.appendChild(option);

  // populate the minutes <select> with the 60 hours of each minute
  for(var i = 0; i <= 59; i++) {
    var option = document.createElement('option');
    option.textContent = (i < 10) ? ("0" + i) : i;
    minuteSelect.appendChild(option);
  }
}

// when the month or year <select> values are changed, rerun populateDays()
// in case the change affected the number of available days
yearSelect.onchange = function() {
  populateDays(monthSelect.value);
}

monthSelect.onchange = function() {
  populateDays(monthSelect.value);
}

//preserve day selection
var previousDay;

// update what day has been set to previously
// see end of populateDays() for usage
daySelect.onchange = function() {
  previousDay = daySelect.value;
}
<?php } ?>

</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
