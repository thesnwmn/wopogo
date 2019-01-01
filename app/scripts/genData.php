<?php

header("Content-type: text/plain");
disable_ob();

require_once dirname(__FILE__).'/../lib/db.php';

$limit = 1;
if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
  $limit = $_GET['limit'];
}

$db = dbConnect();

print("Generating pogoco_gen_stat_summary\n");
flush();
dbQuery("DELETE FROM pogoco_gen_stat_summary WHERE 1", $db);
dbQuery(
  "REPLACE INTO pogoco_gen_stat_summary (stat, value) ".
  "SELECT pts.stat as stat, SUM(pts.value) as value ".
  "FROM pogoco_trainer_stat pts ".
  "WHERE pts.timestamp = (SELECT t.timestamp ".
                           "FROM pogoco_trainer_stat t ".
                           "WHERE t.trainer = pts.trainer ".
                             "AND t.stat = pts.stat ".
                           "ORDER BY t.timestamp DESC ".
                           "LIMIT 1) ".
  "GROUP BY pts.stat",
$db);
print("complete\n\n");
flush();

print("Generating pogoco_gen_stat_summary\n");
flush();
dbQuery("DELETE FROM pogoco_gen_last_updated WHERE 1", $db);
dbQuery(
  "REPLACE INTO pogoco_gen_last_updated (trainer, timestamp) ".
  "SELECT ".
    "pt.id as trainer, ".
    "( ".
      "SELECT timestamp ".
      "FROM pogoco_trainer_stat_latest ptsl ".
      "WHERE ptsl.trainer = pt.id ".
      "ORDER BY timestamp DESC ".
      "LIMIT 1 ".
    ") as timestamp ".
  "FROM pogoco_trainer pt ",
$db);
print("complete\n\n");
flush();

print("Fetching stat list\n");
flush();
$dbResult = dbQuery("SELECT ps.* FROM pogoco_stat ps");
$stats = array();
if ($dbResult->num_rows > 0) {
  while($row = $dbResult->fetch_assoc()) {
    array_push($stats, $row['id']);
  }
}
print_r($stats);
print("\n");
print("complete\n\n");
flush();

foreach ($stats as $stat) {

  print("Generating pogoco_gen_leaderboard for $stat\n");
  flush();

  dbQuery("DELETE FROM pogoco_gen_leaderboard WHERE stat = '$stat'", $db);
  dbQuery(
    "REPLACE INTO pogoco_gen_leaderboard (stat, position, trainer, value) ".
    "SELECT ".
        "t.stat, ".
        "r.rank as position, ".
        "t.trainer, ".
        "t.value ".
    "FROM ".
        "pogoco_trainer_stat_latest t ".
        "INNER JOIN ( ".
            "SELECT ".
                "trainer, ".
                "value, ".
                "CASE value ".
                    "WHEN @curValue THEN @curRow := @curRow ".
                    "ELSE @curRow := @curRow + 1 ".
                "END AS rank, ".
                "@curValue := value AS cValue ".
            "FROM pogoco_trainer_stat_latest i ".
            "CROSS JOIN (SELECT @curRow := 0, @curValue := -1) var ".
            "WHERE stat ='$stat' ".
            "ORDER BY value DESC".
        ") AS r ON r.trainer = t.trainer, ".
        "pogoco_trainer pt ".
    "WHERE t.stat = '$stat' ".
     "AND pt.id = t.trainer ".
    "ORDER BY cast(r.rank as unsigned)",
  $db);

  print("complete\n\n");
  flush();
}
unset($stat);

for ($x = 0; $x <= $limit; $x++) {
  generateMonthlyData($db, $x);
  foreach ($stats as $stat) {
    generateMonthlyLeaderboard($db, $x, $stat);
  }
  unset($stat);
}

print("All complete\n\n");
flush();

$db->close();

function generateMonthlyData($db, $age) {

  dbQuery(
    "SET @current = UTC_TIMESTAMP() - INTERVAL $age MONTH;",
    $db);

  print("Deleting pogoco_trainer_stat_monthly ($age months old)\n");
  flush();

  dbQuery(
    "DELETE FROM pogoco_trainer_stat_monthly WHERE ".
    "month = MONTH(@current) AND year = YEAR(@current)",
    $db);

  print("complete\n\n");
  flush();

  print("Generating pogoco_trainer_stat_monthly ($age months old)\n");
  flush();

  dbQuery(
    "REPLACE INTO pogoco_trainer_stat_monthly (year, month, stat, trainer, value) ".
    "SELECT * ".
    "FROM ( ".
      "SELECT YEAR(@current) as year, MONTH(@current) as month, stat, trainer, MAX(value) - MIN(value) as value ".
  	  "FROM pogoco_trainer_stat ".
  	  "WHERE MONTH(timestamp) = MONTH(@current) AND YEAR(timestamp) = YEAR(@current) ".
  	  "GROUP BY stat, trainer) as data ".
    "WHERE value > 0", $db);

  print("complete\n\n");
  flush();
}

function generateMonthlyLeaderboard($db, $age, $statId) {

  dbQuery(
    "SET @current = UTC_TIMESTAMP() - INTERVAL $age MONTH;",
    $db);

  print("Deleting pogoco_gen_leaderboard_monthly for $statId ($age months old)\n");
  flush();

  dbQuery(
    "DELETE FROM pogoco_gen_leaderboard_monthly WHERE ".
    "month = MONTH(@current) AND year = YEAR(@current) AND stat = '$statId'",
    $db);

  print("complete\n\n");
  flush();

  print("Generating pogoco_gen_leaderboard_monthly for $statId ($age months old)\n");
  flush();

  dbQuery(
    "REPLACE INTO pogoco_gen_leaderboard_monthly (month, year, stat, position, value, trainer) ".
    "SELECT ".
        "MONTH(@current) as month, ".
        "YEAR(@current) as year, ".
        "t.stat, ".
        "r.rank as position, ".
        "t.value, ".
        "t.trainer ".
    "FROM ".
        "pogoco_trainer_stat_monthly t ".
        "INNER JOIN ( ".
            "SELECT ".
                "year, ".
                "month, ".
                "trainer, ".
                "value, ".
                "CASE value ".
                    "WHEN @curValue THEN @curRow := @curRow ".
                    "ELSE @curRow := @curRow + 1 ".
                "END AS rank, ".
                "@curValue := value AS cValue ".
            "FROM pogoco_trainer_stat_monthly i ".
            "CROSS JOIN (SELECT @curRow := 0, @curValue := -1) var ".
            "WHERE stat ='".$statId."' AND month = MONTH(@current) AND year = YEAR(@current) ".
            "ORDER BY value DESC".
        ") AS r ON r.trainer = t.trainer AND r.year = t.year AND r.month = t.month, ".
        "pogoco_trainer pt ".
        "WHERE t.stat = '".$statId."' ".
         "AND pt.id = t.trainer ".
         "AND t.month = MONTH(@current) ".
         "AND t.year = YEAR(@current) ",
    $db);

  print("complete\n\n");
  flush();
}
function disable_ob() {
    // Turn off output buffering
    ini_set('output_buffering', 'off');
    // Turn off PHP output compression
    ini_set('zlib.output_compression', false);
    // Implicitly flush the buffer(s)
    ini_set('implicit_flush', true);
    ob_implicit_flush(true);
    // Clear, and turn off output buffering
    while (ob_get_level() > 0) {
        // Get the curent level
        $level = ob_get_level();
        // End the buffering
        ob_end_clean();
        // If the current level has not changed, abort
        if (ob_get_level() == $level) break;
    }
    // Disable apache output buffering/compression
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', '1');
        apache_setenv('dont-vary', '1');
    }
}

?>
