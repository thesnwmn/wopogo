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
</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
