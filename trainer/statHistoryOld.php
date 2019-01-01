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
  $trainer = getTrainer($_GET['trainer'], false);
  $stats = getStats();
  $history = getStatHistory($trainer['details']['id'], $_GET['stat']);
} catch (Exception $e) {
  webException($e);
}

$dataArr = array();

?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<?php if (!array_key_exists($statId, $stats['stats'])) { ?>

  <p>Unrecognised stat</p>

<?php } else { ?>

<div class="row">
  <h1><?php echo $trainer['details']['name']; ?>:<br>
      <?php echo $stats['stats'][$statId]['details']['title']; ?></h1>
</div>

<div class="row">
  <h3>Plot</h3>
</div>

<div class="row">
  <canvas id="theChart" height="150vmin"></canvas>
</div>

<div class="row">
  <h3>Data</h3>
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
    <tbody>
      <?php foreach ($history['entries'] as $entry) { ?>
        <tr class="js-data-row">
          <td class="js-timestamp" data-value="<?=$entry['timestamp']?>"><?=displayDateTime($entry['timestamp'])?></td>
          <td class="js-value" data-value="<?=$entry['value']?>"><?=number_format($entry['value'])?></td>
          <?php if ($trainer['flags']['editable'] === true) { ?> <td class="js-actions nowrap"></td> <?php } ?>
          <?php array_push($dataArr, "{t:'".$entry['timestamp']."',y:".$entry['value']."}") ?>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<?php } ?>

<script>

window.onload = function() {

  var ctx = document.getElementById("theChart").getContext('2d');
  var myRadarChart = new Chart(ctx, {
      type: 'line',
      data: {
        datasets: [{
          data: [<?php echo implode(",", $dataArr); ?>]
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
              labelString: "<?php echo $stats['stats'][$statId]['details']['data_unit'] ?>"
            }
          }],
          xAxes: [{
            type: "time",
            distribution: 'linear',
            time: {
              unit: "day"
            }
          }]
        }
      }
  });
}
</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
