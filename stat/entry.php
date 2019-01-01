<?php

require_once dirname(__FILE__).'/../app/web.php';
require_once dirname(__FILE__).'/../app/lib/trainers.php';
require_once dirname(__FILE__).'/../app/lib/stats.php';

try {

  $trainerParam = "";
  if (!isset($_GET['id']) && !isset($_GET['name'])) {
    webBadRequest("Must supply one of 'id' or 'name'");
  } else if (isset($_GET['id']) && isset($_GET['name'])) {
    webBadRequest("Must only supply one of 'id' or 'name'");
  } else if (isset($_GET['id'])) {
    $trainerParam = $_GET['id'];
  } else if (isset($_GET['name'])) {
    $trainerParam = $_GET['name'];
  } else {
    webInternalServerError("God knows");
  }

  $trainer = GetTrainer($trainerParam);
  $trainerStats = GetTrainerStats(true, $trainerParam)['stats'];
  $stats = GetStats(true)['stats'];
  $categories = GetStatCategories(true)['categories'];

} catch (Exception $e) {
  webException($e);
}
?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1><?php echo $trainer['name']; ?></h1>

<?php if ( !$session->isLoggedIn() ) { ?>

  <p>Must be logged in to update stats trainers.</p>

<?php } else if ( $trainer['flags']['editable'] !== true ) { ?>

  <p>No permission to edit this trainer's stats</p>

<?php } else { ?>

  <p>Fill in any (or all) of the stats fields with your latest values press save
  at the end of the page.</p>

  <form id="stats-form" onSubmit="return SubmitStats()">

  <?php foreach ( $categories as $catId => $catDef ) { ?>

    <div class="section">
    <h3><?php echo $catDef['title']; ?></h3>

    <?php

      $rows = array();

      foreach ( $stats as $statId => $stat ) {
        if ( $stat['stat_category'] === $catId ) {

          $row = array();
          $row['medal'] = "none";
          $row['weight'] = $stat['weight'];

          $placeholder = "No current value";
          if (array_key_exists($statId, $trainerStats)) {

            $placeholder = "Current: ".number_format($trainerStats[$statId]['value']);

            if (!is_null($stat['gold_threshold']) && $trainerStats[$statId]['value'] >= $stat['gold_threshold']) {
              $row['medal'] = "gold";
            } else if (!is_null($stat['silver_threshold']) && $trainerStats[$statId]['value'] >= $stat['silver_threshold']) {
              $row['medal'] = "silver";
            } else if (!is_null($stat['bronze_threshold']) && $trainerStats[$statId]['value'] >= $stat['bronze_threshold']) {
              $row['medal'] = "bronze";
            }
          }

          $row['html'] = "";
          $row['html'] .= "<div class='four columns'>";
          $row['html'] .= "<label for='".$statId."' class='u-full-width'>".$stat['title']."</label>";
          $row['html'] .= "<input type='text' class='u-full-width' placeholder='".$placeholder."' name='".$statId."' data-collection='stats'>";
          $row['html'] .= "</div>";

          array_push($rows, $row);
        }
      }

      usort($rows, "compareStats");

    $i = 0;

    foreach ( $rows as $row ) {

        if ($i++ === 0) { echo "<div class='row'>"; }

          echo $row['html'];

        if ($i === 3) { $i = 0; echo "</div>"; }

    }

    if ($i !== 0) { echo "</div>"; }

  } ?>

      <div class="section">
      <h3>Date</h3>

      <div class="row">
        <div class="eight columns">
          <label>Date (blank for today)</label>
          <!--<input type="date" id="date" class="u-full-width native-timestamp" name="timestamp">-->
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

    </div>
</div>

<div class="section">
  <h2>Actions</h2>

  <div class="row">
    <button type="submit" class="four columns button-primary">Save</button>
    <a class="four columns button" onclick="resetPage()">Reset</a>
    <a class="four columns button" onclick="copyValues()">Copy Values</a>
  </div>
    <div class="row">
    <div class="twelve columns js-form-result block error"></div>
  </div>
</div>

  </form>

<?php } ?>

<script>

function copyValues() {

  var formEle = document.querySelector("#stats-form");
  if (formEle !== null) {

<?php foreach ( $stats as $statId => $stat ) { ?>

    var input = formEle.querySelector("input[name='<?=$statId?>']");
    if (typeof input !== "undefined") {
      var currentValue = input.value;
      if (currentValue === "") {
        input.value = "<?=$trainerStats[$statId]['value']?>";
      }
    }
<?php } ?>

  }
}

function SubmitStats() {

  var formEle = document.querySelector("#stats-form");

  if (formEle !== null) {

    disableForm(formEle);

    var resultEle = formEle.querySelector(".js-form-result");
    if (resultEle !== null) {
      resultEle.innerHTML = "";
    }
    var messages = formEle.querySelectorAll(".input-message");
    messages.forEach(function(messageEle) {
      messageEle.parentNode.removeChild(messageEle);
    });

    var body = {
      trainer: "<?=$trainer['name']?>",
      stats: []
    };
    formEle.querySelectorAll("input").forEach(function(ele) {
        if (ele.type === "text" && ele.value !== "") {
          body.stats.push({ stat: ele.name, value: ele.value });
        }
    });

    //
    // Get date/time
    //

    var timestampError = null;
    var date = "";
    var time = "";
    var timestamp = null;

    if (timestampFallback) {

      var day = daySelect.value;
      var month = monthSelect.value;
      var year = yearSelect.value;

      if (day === "" && month === "" && year === "") {
        // Nothing, defaulted later
      } else if (day === "" || month === "" || year === "") {
        timestampError = "Incomplete date";
      } else {
        date = year + "-" + month + "-" + day;
      }

    } else {

      date = datePicker.value;
    }

    if (timestampError === null && date === "") {
      var d = new Date();
      date = d.getFullYear() + "/" +
             ("0" + (d.getMonth()+1)).slice(-2) + "/" +
             ("0" + d.getDate()).slice(-2);
      if (hourSelect.value === "" && minuteSelect.value === "") {
        // No time and no date, select now
        time = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
      } else {
        timestampError = "Time not allowed without date";
      }
    } else {
      date = date.replace(/-/g, '/');
    }

    if (timestampError === null && time === "") {
      if (hourSelect.value === "" && minuteSelect.value === "") {
        var d = new Date("2000/05/01 00:00");
        time = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getUTCMinutes()).slice(-2);
      } else if (hourSelect.value === "" || minuteSelect.value === "") {
        timestampError = "Incomplete time";
      } else {
        time = hourSelect.value + ":" + minuteSelect.value;
      }
    }

    if (timestampError !== null) {
      resultEle.textContent = timestampError;
      return false;
    } else {
      var d = new Date(date + " " + time);
      body.timestamp = d.getUTCFullYear() + "-" +
               ("0" + (d.getUTCMonth()+1)).slice(-2) + "-" +
               ("0" + d.getUTCDate()).slice(-2) + " " +
               ("0" + d.getUTCHours()).slice(-2) + ":" +
               ("0" + d.getUTCMinutes()).slice(-2);

    }

    function displayFieldError(error) {
      var displayed = false;
      if (typeof error.context !== "undefined" &&
          typeof error.context.stat !== "undefined") {
        var fields = formEle.querySelectorAll("[name=" + error.context.stat + "]");
        if (fields.length > 0) {
          fields.forEach(function(field) {
            var errorText = document.createElement("div");
            errorText.textContent = error.text;
            errorText.className = "input-message error";
            field.parentNode.insertBefore(errorText, field.nextSibling);
            displayed = true;
          });
        }
      }
      return displayed;
    }

    httpPostAsync(
        '<?php echo $site_root ?>/app/api/v1/stat/stats.php',
        JSON.stringify(body),
        function(response) {
          window.location.href = '<?=$site_root?>/trainer/trainer.php?name=<?=$trainer['name']?>';
        },
        function(response) {
          enableForm(formEle);
          var fieldErrors = false;
          var nonFieldErrors = false;
          response.errors.forEach(function(error) {
            if (!displayFieldError(error)) {
              nonFieldErrors = true;
              var errEle = document.createElement("div");
              errEle.textContent = error.text;
              resultEle.appendChild(errEle);
            } else {
              fieldErrors = true;
            }
          });
          if (fieldErrors && !nonFieldErrors) {
            var errEle = document.createElement("div");
            errEle.textContent = "See fields for errors";
            resultEle.appendChild(errEle);
          }
        }
      );
  }

  return false;
}
function resetPage() {
  window.location.reload();
}


/* Date time picker
 */

//var nativePicker = document.querySelector('.native-timestamp');
var fallbackPicker = document.querySelector('.fallback-timestamp');

var datePicker = document.querySelector('#date');
var yearSelect = document.querySelector('#year');
var monthSelect = document.querySelector('#month');
var daySelect = document.querySelector('#day');
var hourSelect = document.querySelector('#hour');
var minuteSelect = document.querySelector('#minute');

// hide fallback initially
//fallbackPicker.style.display = 'none';

var timestampFallback = false;
// test whether a new datetime-local input falls back to a text input or not
//var test = document.createElement('input');
//test.type = 'datetime-local';
// if it does, run the code inside the if() {} block
//if (test.type === 'text') {

  timestampFallback = true;

  // hide the native picker and show the fallback
  //nativePicker.style.display = 'none';
  fallbackPicker.style.display = 'block';

  // populate the days and years dynamically
  // (the months are always the same, therefore hardcoded)
  populateDays(monthSelect.value);
  populateYears();
//}
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
</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
