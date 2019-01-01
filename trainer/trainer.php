<?php

require_once dirname(__FILE__).'/../app/web.php';
require_once dirname(__FILE__).'/../app/lib/stats.php';
require_once dirname(__FILE__).'/../app/lib/trainers.php';

$trainer = NULL;
$stat = NULL;

try {
  $trainerParam = NULL;
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
  $trainerLevel = GetTrainerLevel($trainerParam);
  $stats = GetStats(true)['stats'];
} catch (Exception $e) {
  webException($e);
}

function buildMedalTable($catId) {

  global $site_root;

  global $stats;
  global $trainer;
  global $trainerStats;

    $rows = array();

    foreach ( $stats as $statId => $stat ) {
      if ( $stat['stat_category'] === $catId ) {

        $row = array();
        $row['medal'] = "none";
        $row['weight'] = $stat['weight'];

        $rowHtml = "";

        $textValue = "0";
        $rankValue = "";
        $type = "shadow";
        if (array_key_exists($statId, $trainerStats)) {
          $value = $trainerStats[$statId]['value'];
          $textValue = number_format($trainerStats[$statId]['value']);
          $rankValue = number_format($trainerStats[$statId]['rank']);
          if (!is_null($stat['gold_threshold']) && $trainerStats[$statId]['value'] >= $stat['gold_threshold']) {
            $type = "gold";
            $row['medal'] = "gold";
          } else if (!is_null($stat['silver_threshold']) && $trainerStats[$statId]['value'] >= $stat['silver_threshold']) {
            $type = "silver";
            $row['medal'] = "silver";
          } else if (!is_null($stat['bronze_threshold']) && $trainerStats[$statId]['value'] >= $stat['bronze_threshold']) {
            $type = "bronze";
            $row['medal'] = "bronze";
          }
        }

        $rowHtml .= "<div>".$textValue."</div>";

        $rowHtml .= "<div>";
        $rowHtml .= "<a href=".$site_root."/stat/stat.php?id=".$statId.">";
        $rowHtml .= "<img src='../res/img/medal_small/".$statId."_".$type.".png'>";
        $rowHtml .= "</a>";
        $rowHtml .= "</div>";

        $rowHtml .= "<div>#$rankValue</div>";

        $rowHtml .= "<div>";

        $rowHtml .= "<a href=".$site_root."/stat/stat.php?id=".$statId.">";
        $rowHtml .= "<img src='".$site_root."/res/img/list_light_grey_24x24.png'>";
        $rowHtml .= "</a>";

        $rowHtml .= "<a href=".$site_root."/trainer/statHistory.php";
        $rowHtml .= "?trainer=".$trainer['name'];
        $rowHtml .= "&stat=".$statId.">";
        $rowHtml .= "<img src='".$site_root."/res/img/history_light_grey_24x24.png'></a>";

        $rowHtml .= "</div>";

        $row['html'] = $rowHtml;

        array_push($rows, $row);
      }
    }

    usort($rows, "compareStats");

    $i = 0;
   ?>
      <div class="half-column">
      <?php
    foreach ($rows as $row) {
        if ($i++ === 0) { ?>
          <div class='row'>
        <?php } ?>

          <div class="three columns center spaced">
            <?php echo $row['html'] ?>
          </div>

        <?php if ($i > 3) { $i = 0; ?>
          </div>
        <?php }
    } ?>
    </div>
<?php }


function buildStatTable($catId) {

  global $site_root;

  global $stats;
  global $trainer;
  global $trainerStats;
 ?>
    <div class="row">
      <table class="u-full-width">
        <thead>
          <th>Statistic</th>
          <th>Value</th>
          <th>Rank</th>
        </thead>
        <tbody>

    <?php foreach ( $stats as $statId => $stat ) { ?>
      <?php if ( $stat['stat_category'] === $catId ) { ?>

        <?php
          $textValue = "";
          $rankText = "";
          if (array_key_exists($statId, $trainerStats)) {
            $textValue = number_format($trainerStats[$statId]['value']);
            $rankText = number_format($trainerStats[$statId]['rank']);
          }
        ?>

        <tr>
          <td><?php echo $stat['title'] ?>
            <a href="<?=$site_root?>/stat/stat.php?id=<?=$statId?>"
            ><img src="<?=$site_root?>/res/img/list_light_grey_24x24.png"></a>
            <a href="<?=$site_root?>/trainer/statHistory.php?trainer=<?=$trainer['name']?>&stat=<?=$statId?>"
            ><img src="<?=$site_root?>/res/img/history_light_grey_24x24.png">
            </a>
          </td>
          <td><?php echo $textValue ?></td>
          <td><?php echo $rankText ?></td>
        </tr>

      <?php } ?>
    <?php } ?>
    </tbody>
  </table>
</div>
<?php }
?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>



<div class="row">
  <h1><?php echo $trainer['name']; ?></h1>
</div>

<div class="row">
  <div class="two columns">
    <label class="u-fill-width">Updated:</label>
  </div>
  <div class="eight columns">
    <span><?=displayDateTime($trainer['last_update'])?></span>
  </div>
</div>

<?php if ($trainer['flags']['editable'] === true) { ?>
<div class="row">
  <a class="button" href="<?php echo $site_root ?>/stat/entry.php?name=<?php echo $trainer['name']; ?>">Update stats</a>
</div>
<?php } ?>

<div class="row">
  <h3>Experience</h3>
</div>

<div class="row spaced">
  <div class="two columns">
    <label class="u-fill-width">Level:</label>
  </div>
  <div class="eight columns">
    <?=$trainerLevel['level']?>
    <span class="align-right"><a href="<?=$site_root?>/stat/stat.php?id=xp"
      ><img src="<?=$site_root?>/res/img/list_light_grey_24x24.png"></a>
      <a href="<?=$site_root?>/trainer/statHistory.php?trainer=<?=$trainer['name']?>&stat=xp"
      ><img src="<?=$site_root?>/res/img/history_light_grey_24x24.png">
      </a>
    </span>
  </div>
</div>

<?php if (array_key_exists('next', $trainerLevel)) { ?>
  <div class="row spaced">
    <div class="two columns">
      <label class="u-fill-width">To <?php echo $trainerLevel['level'] + 1 ?>:</label>
    </div>
    <div class="ten columns progress-container">
      <div class="progress-bar u-full-width team-fill-<?php echo $trainer['team']; ?>" style="width:<?php echo $trainerLevel['next']['progress']; ?>%">
        <?php if ($trainerLevel['next']['progress'] > 50) { ?>
            <div class="progress-text"><?php echo number_format($trainerLevel['next']['earnt'])." / ".number_format($trainerLevel['next']['required']); ?></div>
          </div>
        <?php } else { ?>
          </div>
          <div class="progress-text"><?php echo number_format($trainerLevel['next']['earnt'])." / ".number_format($trainerLevel['next']['required']); ?></div>
        <?php } ?>
    </div>
  </div>
<?php } ?>

<div class="row spaced">
  <div class="two columns">
    <label class="u-fill-width">To 40:</label>
  </div>
  <div class="ten columns progress-container">
    <div class="progress-bar u-full-width team-fill-<?php echo $trainer['team']; ?>" style="width:<?php echo $trainerLevel['max']['progress']; ?>%">
  <?php if ($trainerLevel['max']['progress'] >= 100) { ?>
      <div class="progress-text"><?php echo number_format($trainerLevel['xp']); ?></div>
    </div>
  <?php } else if ($trainerLevel['max']['progress'] > 50) { ?>
      <div class="progress-text"><?php echo number_format($trainerLevel['xp'])." / ".number_format($trainerLevel['max']['required']); ?></div>
    </div>
  <?php } else { ?>
    </div>
    <div class="progress-text"><?php echo number_format($trainerLevel['xp'])." / ".number_format($trainerLevel['max']['required']); ?></div>
  <?php } ?>
  </div>
</div>

<div class="row">
  <h3>Statistics</h3>
</div>

<?php echo buildStatTable('STAT', $stats, $trainer); ?>

<div class="row">
  <h3>Medals</h3>
</div>

<?php echo buildMedalTable('GENERAL_MEDAL', $stats, $trainer); ?>

<div class="row">
  <h3>Type Medals</h3>
</div>
<?php echo buildMedalTable('TYPE_MEDAL', $stats, $trainer); ?>
</div>
<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
