<?php
require_once dirname(__FILE__).'/app/web.php';
require_once dirname(__FILE__).'/app/lib/stats.php';
require_once dirname(__FILE__).'/app/lib/trainers.php';

$stats = GetStats(true);
$xpSummary = GetStatSummary("xp");
$typeStatSummary = GetStatsSummaryForCategory(true, "TYPE_MEDAL");
$updatedTrainers = GetLastUpdatedTrainers();

$xp = 0;
if (array_key_exists('total', $xpSummary)) {
    $xp = number_format($xpSummary['total']);
}

?>

<?php require_once dirname(__FILE__).'/res/include/top.php';?>

<div class="row">
  <h3>Experience</h3>
  <p>The community has earnt a combined total of <?php echo $xp ?> experience.</p>
</div>

<div class="row">
  <h3>Typed catch breakdown</h3>
  <p><canvas id="typeChart" height="200vmin"></canvas></p>
</div>

<div class="row">
  <h3>Recently updated</h3>
  <table class="u-full-width">
    <thead>
      <tr>
        <th width="50%">Name</th>
        <th width="50%">Updated</th>
        <th class="center">Team</th>
    </thead>
    <tbody>
      <?php foreach ($updatedTrainers['trainers'] as $trainer) { ?>
        <tr>
          <td>
            <a href="<?php echo $site_root ?>/trainer/trainer.php?name=<?php echo $trainer['name']; ?>">
              <?php echo $trainer['name']; ?>
            </a>
          </td>
          <td><?=displayDateTime($trainer['last_update'])?></td>
          <td class="center"><div class="icon icon-img-team-<?php echo $trainer['team']; ?>"></div></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<script>
<?php
$labelArr = array();
$dataArr = array();
foreach ($typeStatSummary['stats'] as $entry) {
  array_push($labelArr, "'".$stats['stats'][$entry['stat']]['title']."'");
  array_push($dataArr, $entry['total']);
}
?>
var ctx = document.getElementById("typeChart").getContext('2d');
var myRadarChart = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: [<?php echo implode(",", $labelArr); ?>],
      datasets: [{
        data: [<?php echo implode(",", $dataArr); ?>]
      }]
    },
    options: {
      maintainAspectRatio: true,
      legend: {
        display: false
      },
      scale : {
        ticks: {
          display: false,
          beginAtZero : true
        }
      }
    }
});
</script>

<?php require_once dirname(__FILE__).'/res/include/tail.php'; ?>
