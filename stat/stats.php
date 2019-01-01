<?php

require_once dirname(__FILE__).'/../app/web.php';
require_once dirname(__FILE__).'/../app/lib/stats.php';

try {
  $period = "";
  $params = "";
  $stats = GetStats(true)['stats'];
  $categories = GetStatCategories(true)['categories'];
  $months = GetAvailableMonths()['months'];
  if (isset($_GET['year']) && isset($_GET['month'])) {
    $leaders = GetStatsMonthlyLeaders(true, $_GET['year'], $_GET['month'])['stats'];
    $period = ":<br>".date('F Y', mktime(0, 0, 12, $_GET['month'], 5, $_GET['year']));
    $params = "&year=".$_GET['year']."&month=".$_GET['month'];
  } else {
    $leaders = GetStatsLeaders(true)['stats'];
  }
} catch (Exception $e) {
  webException($e);
}

function buildTable($stats, $type) {

  global $site_root, $leaders, $params;

  $html = "";

  $html .= "<table class='u-full-width'>\n";
  $html .= "  <thead>\n";
  $html .= "    <tr>\n";
  $html .= "      <th width='40%'>Statistic</th>\n";
  $html .= "      <th width='20%'>Highest</th>\n";
  $html .= "      <th width='30%'>Trainer</th>\n";
  $html .= "      <th width='10%' class='center'>Team</th>\n";
  $html .= "    </tr>\n";
  $html .= "  </thead>\n";
  $html .= "  <tbody>\n";

  foreach ( $stats as $statId => $stat ) {
    if ( $stat['stat_category'] === $type ) {

  $html .= "    <tr>\n";
  $html .= "      <td>\n";
  $html .= "        ";
$html .= "<a href='".$site_root."/stat/stat.php?id=".$statId.$params."'>";
  $html .= $stat['title'];
  $html .= "</a>\n";
  $html .= "      </td>\n";

  if (!array_key_exists($statId, $leaders) ||
      !array_key_exists('leaders', $leaders[$statId])) {
    $html .= "<td></td><td></td><td></td>";
  } else {

    $leaderCount = sizeof($leaders[$statId]['leaders']);

    if ($leaderCount > 3) {

      $html .= "<td>".number_format($leaders[$statId]['value'])."</td>";
      $html .= "<td>Many (".$leaderCount.")</td>";
      $html .= "<td></td>";
      $html .= "</tr>";

    } else {

    $first = true;

    foreach ( $leaders[$statId]['leaders'] as $leader ) {

      if ($first === true) {
        $first = false;
        $html .= "<td>".number_format($leaders[$statId]['value'])."</td>";
      } else {
        $html .= "<tr><td></td><td></td>";
      }

      $html .= "      <td>\n";
      $html .= "        ";
      $html .= "<a href='".$site_root."/trainer/trainer.php?name=".$leader['name']."'>";
      $html .= $leader['name'];
      $html .= "</a>\n";

      $html .= "      <td class='center'>\n";
      $html .= "        ";
      $html .= "<div class='icon icon-img-team-".$leader['team']."'></div>";
      $html .= "      </td></tr>\n";
    }
  }

  }
    }
  }
  $html .= "  </tbody>\n";
  $html .= "</table>\n";

  return $html;
}
?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1>Leaderboards<?=$period?></h1>

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

<?php foreach ( $categories as $catDef ) { ?>

<h3><?php echo $catDef['title']; ?></h3>

<?php echo buildTable($stats, $catDef['id']); ?>

<?php } ?>

<script>
function changedMonth(e) {
  var option = e.target.value;
  if (option === "all") {
    window.location.href = "stats.php";
  } else {
    var parts = option.split("-");
    window.location.href = "stats.php?year=" + parts[1] + "&month=" + parts[0];
  }
  return false;
}
</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
