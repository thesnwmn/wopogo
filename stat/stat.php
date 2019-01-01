<?php

require_once dirname(__FILE__).'/../app/web.php';
require_once dirname(__FILE__).'/../app/lib/stats.php';

$stat = NULL;

try {
  if (!isset($_GET['id'])) {
    webBadRequest("Must supply 'id'");
  } else {
    $period = "";
    $stat = GetStat($_GET['id']);
    $months = GetAvailableMonths()['months'];
    if (isset($_GET['year']) && isset($_GET['month'])) {
      $leaderboard = GetStatMonthlyLeaderboard($_GET['id'], $_GET['year'], $_GET['month'])['leaderboard'];
      $period = ":<br>".date('F Y', mktime(0, 0, 12, $_GET['month'], 5, $_GET['year']));
    } else {
      $leaderboard = GetStatLeaderboard($_GET['id'])['leaderboard'];
    }
  }
} catch (Exception $e) {
  webException($e);
}
?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1><?php echo $stat['title']; ?><?=$period?></h1>

<select onchange="changedMonth(event);">
  <option value="" selected>Select period...</option>
  <option value="all">All time</option>
<?php
  foreach ( $months as $entry ) {
    $label = date('F Y', mktime(0, 0, 12, $entry['month'], 5, $entry['year']));
    $value = date('m-Y', mktime(0, 0, 12, $entry['month'], 5, $entry['year']));
    echo "<option value='$value'>$label</option>";
  }
?>
</select>

<p><?php echo $stat['description']; ?></p>

<h3>Leaderboard</h3>

<table class="u-full-width">
  <thead>
    <tr>
      <th>Rank</th>
      <th>Trainer</th>
      <th>Value</th>
      <th class="center">Team</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 1; ?>
    <?php foreach ($leaderboard as $entry) { ?>
      <tr>
        <td><?php echo $entry['rank'] ?></td>
        <td>
          <a href="<?php echo $site_root ?>/trainer/trainer.php?name=<?php echo $entry['name']; ?>">
            <?php echo $entry['name']; ?>
          </a>
        </td>
        <td><?php echo number_format($entry['value']); ?></td>
        <td class="center"><div class="icon icon-img-team-<?php echo $entry['team']; ?>"></div></td>
      </tr>
    <?php } ?>
  </tbody>
</table>

<script>
function changedMonth(e) {
  var option = e.target.value;
  if (option === "all") {
    window.location.href = "stat.php?id=<?=$_GET['id']?>";
  } else {
    var parts = option.split("-");
    window.location.href = "stat.php?id=<?=$_GET['id']?>&year=" + parts[1] + "&month=" + parts[0];
  }
  return false;
}
</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
