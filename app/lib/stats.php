<?php

require_once dirname(__FILE__).'/db.php';


function GetStatCategory($categoryId) {

  $dbResult = dbQuery(
    "SELECT * FROM pogoco_stat_category WHERE id = '".$categoryId."'"
  );

  $result = array();
  if ($dbResult->num_rows === 0) {
    throw new Exception("No matching category", 400);
  } else if ($dbResult->num_rows > 1) {
    throw new Exception("Multiple matching categories", 500);
  } else {
    $result = $dbResult->fetch_assoc();
  }

  return $result;
}
function GetStatCategories($map) {

  $dbResult = dbQuery(
    "SELECT * FROM pogoco_stat_category ORDER BY weight ASC"
  );

  $result = array();
  $result['categories'] = array();
  if ($dbResult->num_rows > 0) {
    while($row = $dbResult->fetch_assoc()) {
      if ($map) {
        $result['categories'][$row['id']] = $row;
      } else {
        array_push($result['categories'], $row);
      }
    }
  }

  return $result;
}





function GetStat($statId) {

  $dbResult = dbQuery("SELECT * FROM pogoco_stat WHERE id ='".$statId."'");

  $result = array();
  if ($dbResult->num_rows === 0) {
    throw new Exception("No matching stat", 400);
  } else if ($dbResult->num_rows > 1) {
    throw new Exception("Multiple matching stats", 500);
  } else {
    $result = $dbResult->fetch_assoc();
  }

  return $result;
}

function GetStats($map) {
  return _GetStats(
    $map,
    "SELECT ps.* ".
    "FROM pogoco_stat ps, pogoco_stat_category psc ".
    "WHERE psc.id = ps.stat_category ".
    "ORDER BY psc.weight ASC, ps.weight ASC, ps.title ASC"
  );

}
function GetStatsForCategory($map, $categoryId) {
  return _GetStats(
    $map,
    "SELECT ps.* ".
    "FROM pogoco_stat ps, pogoco_stat_category psc ".
    "WHERE psc.id = ps.stat_category ".
      "AND ps.stat_category = '".$categoryId."'".
    "ORDER BY psc.weight ASC, ps.weight ASC, ps.title ASC"
  );
}
function _GetStats($map, $sql) {

  $dbResult = dbQuery($sql);

  $result = array();
  $result['stats'] = array();
  if ($dbResult->num_rows > 0) {
    while($row = $dbResult->fetch_assoc()) {
      if ($map) {
        $result['stats'][$row['id']] = $row;
      } else {
        array_push($result['stats'], $row);
      }
    }
  }
  return $result;
}





function GetStatLeaderboard($statId) {

  $dbResult = dbQuery(
    "SELECT ".
        "r.rank, ".
        "t.value, ".
        "t.trainer, ".
        "pt.name, ".
        "pt.team ".
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
            "WHERE stat ='".$statId."' ".
            "ORDER BY value DESC".
        ") AS r ON r.trainer = t.trainer, ".
        "pogoco_trainer pt ".
    "WHERE t.stat = '".$statId."' ".
     "AND pt.id = t.trainer ".
    "ORDER BY cast(r.rank as unsigned)");

  $result = array();
  $result['leaderboard'] = array();
  if ($dbResult->num_rows > 0) {
    while($row = $dbResult->fetch_assoc()) {
      array_push($result['leaderboard'], $row);
    }
  }

  return $result;
}





function GetStatLeaders($statId) {

  $dbResult = dbQuery(
    "SELECT ".
        "r.rank, ".
        "t.value, ".
        "t.trainer, ".
        "pt.name, ".
        "pt.team ".
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
            "WHERE stat ='".$statId."' ".
            "ORDER BY value DESC, trainer ASC ".
        ") AS r ON r.trainer = t.trainer, ".
        "pogoco_trainer pt ".
    "WHERE t.stat = '".$statId."' ".
      "AND r.rank = 1 ".
      "AND pt.id = t.trainer ");

  $result = array();
  $result['leaders'] = array();
  if ($dbResult->num_rows > 0) {
    while($row = $dbResult->fetch_assoc()) {
      if (!array_key_exists('value', $result)) {
        $result['value'] = $row['value'];
      }
      $trainer = array();
      $trainer['id'] = $row['trainer'];
      $trainer['name'] = $row['name'];
      $trainer['team'] = $row['team'];
      array_push($result['leaders'], $trainer);
    }
  }

  return $result;
}

function GetStatsLeaders($map) {
  return _GetStatsLeaders(
    $map,
    "SELECT ".
        "t.stat, ".
        "r.rank, ".
        "t.value, ".
        "t.trainer, ".
        "pt.name, ".
        "pt.team ".
    "FROM ".
        "pogoco_trainer_stat_latest t ".
        "INNER JOIN ( ".
            "SELECT  ".
                "trainer, ".
                "timestamp, ".
                "value, ".
                "stat, ".
                "CASE stat ".
                    "WHEN @curStat THEN ".
                        "CASE value ".
                            "WHEN @curValue THEN @curRow := @curRow ".
                            "ELSE @curRow := @curRow + 1 ".
                        "END ".
                    "ELSE @curRow := 1 ".
                "END AS rank, ".
                "@curValue := value AS cValue, ".
                "@curStat := stat AS cStat ".
            "FROM pogoco_trainer_stat_latest  ".
            "CROSS JOIN (SELECT @curRow := 0, @curStat := '', @curValue := -1) var ".
            "ORDER BY stat ASC, value DESC, trainer ASC ".
        ") AS r ON r.trainer = t.trainer AND r.stat = t.stat, ".
        "pogoco_trainer pt ".
    "WHERE r.rank = 1 ".
      "AND pt.id = t.trainer ".
    "ORDER BY t.stat, r.rank");
}
function GetStatsLeadersForCategory($map, $categoryId) {
  return _GetStatsLeaders(
    $map,
    "SELECT ".
        "t.stat, ".
        "r.rank, ".
        "t.value, ".
        "t.trainer, ".
        "pt.name, ".
        "pt.team ".
    "FROM ".
        "pogoco_trainer_stat_latest t ".
        "INNER JOIN ( ".
            "SELECT  ".
                "trainer, ".
                "timestamp, ".
                "value, ".
                "stat, ".
                "CASE stat ".
                    "WHEN @curStat THEN ".
                        "CASE value ".
                            "WHEN @curValue THEN @curRow := @curRow ".
                            "ELSE @curRow := @curRow + 1 ".
                        "END ".
                    "ELSE @curRow := 1 ".
                "END AS rank, ".
                "@curValue := value AS cValue, ".
                "@curStat := stat AS cStat ".
            "FROM pogoco_trainer_stat_latest  ".
            "CROSS JOIN (SELECT @curRow := 0, @curStat := '', @curValue := -1) var ".
            "ORDER BY stat, value DESC ".
        ") AS r ON r.trainer = t.trainer AND r.stat = t.stat, ".
        "pogoco_stat s, ".
        "pogoco_trainer pt ".
    "WHERE t.stat = s.id ".
      "AND s.stat_category = '".$categoryId."' ".
      "AND pt.id = t.trainer ".
      "AND r.rank = 1 ".
    "ORDER BY t.stat, r.rank");
}
function _GetStatsLeaders($map, $sql) {

  $dbResult = dbQuery($sql);

  $tempObj = array();
  if ($dbResult->num_rows > 0) {
    while($row = $dbResult->fetch_assoc()) {
      if (!array_key_exists($row['stat'], $tempObj)) {
        $tempObj[$row['stat']] = array();
        if (!$map) {
          $tempObj[$row['stat']]['stat'] = $row['stat'];
        }
        $tempObj[$row['stat']]['value'] = $row['value'];
        $tempObj[$row['stat']]['leaders'] = array();
      }
      $trainer = array();
      $trainer['id'] = $row['trainer'];
      $trainer['name'] = $row['name'];
      $trainer['team'] = $row['team'];
      array_push($tempObj[$row['stat']]['leaders'], $trainer);
    }
  }

  $result = array();
  $result['stats'] = array();
  if ($map) {
    $result['stats'] = $tempObj;
  } else {
    foreach ($tempObj as $key => $value) {
      array_push($result['stats'], $value);
    }
  }

  return $result;
}





function GetStatSummary($statId) {

  $dbResult = dbQuery(
      "SELECT SUM(pts.value) as total ".
      "FROM pogoco_trainer_stat pts ".
      "WHERE pts.timestamp = (SELECT t.timestamp ".
                             "FROM pogoco_trainer_stat t ".
                             "WHERE t.trainer = pts.trainer ".
                               "AND t.stat = pts.stat ".
                             "ORDER BY t.timestamp DESC ".
                             "LIMIT 1) ".
      "  AND pts.stat = '".$statId."'".
      "GROUP BY pts.stat");

  $result = array();
  if ($dbResult->num_rows === 1) {
    $result = $dbResult->fetch_assoc();
  }

  return $result;
}

function GetStatsSummary($map) {
  return _GetStatsSummary(
    $map,
    "SELECT pts.stat, SUM(pts.value) as total ".
    "FROM pogoco_trainer_stat pts ".
    "WHERE pts.timestamp = (SELECT t.timestamp ".
                           "FROM pogoco_trainer_stat t ".
                           "WHERE t.trainer = pts.trainer ".
                             "AND t.stat = pts.stat ".
                           "ORDER BY t.timestamp DESC ".
                           "LIMIT 1) ".
    "GROUP BY pts.stat");
}
function GetStatsSummaryForCategory($map, $categoryId) {
  return _GetStatsSummary(
    $map,
    "SELECT pts.stat, SUM(pts.value) as total ".
    "FROM pogoco_trainer_stat pts, pogoco_stat ps ".
    "WHERE pts.timestamp = (SELECT t.timestamp ".
                           "FROM pogoco_trainer_stat t ".
                           "WHERE t.trainer = pts.trainer ".
                             "AND t.stat = pts.stat ".
                           "ORDER BY t.timestamp DESC ".
                           "LIMIT 1) ".
    "  AND ps.stat_category = '".$categoryId."' ".
    "  AND pts.stat = ps.id ".
    "GROUP BY pts.stat");
}
function _GetStatsSummary($map, $sql) {

  $dbResult = dbQuery($sql);

  $result = array();
  $result['stats'] = array();
  if ($dbResult->num_rows > 0) {
      while($row = $dbResult->fetch_assoc()) {
        if ($map) {
          $result['stats'][$row['stat']] = $row;
        } else {
          array_push($result['stats'], $row);
        }
      }
  }

  return $result;
}





function GetStatHistory($trainer, $statId) {

  $dbResult = dbQuery(
    "SELECT pts.timestamp, pts.value ".
    "FROM pogoco_trainer_stat pts, pogoco_trainer pt ".
    "WHERE pts.stat = '".$statId."' ".
      "AND trainer = pt.id ".
      "AND (pt.id = '".$trainer."' OR pt.name = '".$trainer."') ".
    "ORDER BY pts.timestamp DESC");

  $result = array();
  $result['entries'] = array();
  if ($dbResult->num_rows > 0) {
      while($row = $dbResult->fetch_assoc()) {
        array_push($result['entries'], $row);
      }
  }

  return $result;
}





function GetAvailableMonths() {

  $dbResult = dbQuery(
    "SELECT DISTINCT month, year ".
    "FROM pogoco_trainer_stat_monthly ".
    "ORDER BY year DESC, month DESC");

  $result = array();
  $result['months'] = array();
  while($row = $dbResult->fetch_assoc()) {
    array_push($result['months'], $row);
  }

  return $result;
}
function GetStatsMonthlyLeaders($map, $year, $month) {

  $sql =
    "SELECT ".
        "t.stat, ".
        "t.value, ".
        "t.trainer, ".
        "pt.name, ".
        "pt.team ".
    "FROM ".
        "pogoco_trainer_stat_monthly t ".
        "INNER JOIN ( ".
            "SELECT  ".
                "year, ".
                "month, ".
                "trainer, ".
                "value, ".
                "stat, ".
                "CASE stat ".
                    "WHEN @curStat THEN ".
                        "CASE value ".
                            "WHEN @curValue THEN @curRow := @curRow ".
                            "ELSE @curRow := @curRow + 1 ".
                        "END ".
                    "ELSE @curRow := 1 ".
                "END AS rank, ".
                "@curValue := value AS cValue, ".
                "@curStat := stat AS cStat ".
            "FROM pogoco_trainer_stat_monthly ".
            "CROSS JOIN (SELECT @curRow := 0, @curStat := '', @curValue := -1) var ".
            "WHERE month = $month AND year = $year ".
            "ORDER BY stat ASC, value DESC, trainer ASC ".
        ") AS r ON r.trainer = t.trainer AND r.stat = t.stat AND r.year = t.year AND r.month = t.month, ".
        "pogoco_trainer pt ".
    "WHERE r.rank = 1 ".
      "AND pt.id = t.trainer ".
      "AND t.month = $month ".
      "AND t.year = $year ".
    "ORDER BY t.stat, r.rank";

  $dbResult = dbQuery($sql);

  $tempObj = array();
  if ($dbResult->num_rows > 0) {
    while($row = $dbResult->fetch_assoc()) {
      if (!array_key_exists($row['stat'], $tempObj)) {
        $tempObj[$row['stat']] = array();
        if (!$map) {
          $tempObj[$row['stat']]['stat'] = $row['stat'];
        }
        $tempObj[$row['stat']]['value'] = $row['value'];
        $tempObj[$row['stat']]['leaders'] = array();
      }
      $trainer = array();
      $trainer['id'] = $row['trainer'];
      $trainer['name'] = $row['name'];
      $trainer['team'] = $row['team'];
      array_push($tempObj[$row['stat']]['leaders'], $trainer);
    }
  }

  $result = array();
  $result['stats'] = array();
  if ($map) {
    $result['stats'] = $tempObj;
  } else {
    foreach ($tempObj as $key => $value) {
      array_push($result['stats'], $value);
    }
  }

  return $result;
}
function GetStatMonthlyLeaderboard($statId, $year, $month) {

  $dbResult = dbQuery(
    "SELECT ".
        "r.rank, ".
        "t.value, ".
        "t.trainer, ".
        "pt.name, ".
        "pt.team ".
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
            "WHERE stat ='".$statId."' AND month = $month AND year = $year ".
            "ORDER BY value DESC".
        ") AS r ON r.trainer = t.trainer AND r.year = t.year AND r.month = t.month, ".
        "pogoco_trainer pt ".
    "WHERE t.stat = '".$statId."' ".
     "AND pt.id = t.trainer ".
     "AND t.month = $month ".
     "AND t.year = $year ".
    "ORDER BY cast(r.rank as unsigned)");

  $result = array();
  $result['leaderboard'] = array();
  if ($dbResult->num_rows > 0) {
    while($row = $dbResult->fetch_assoc()) {
      array_push($result['leaderboard'], $row);
    }
  }

  return $result;
}


function InsertStats($obj) {

  global $session;

  $errors = array();

  if (!$session->isLoggedIn()) {
    array_push($errors, buildError("Not logged in"));
    return $errors;
  }

  if (!$session->hasPermission("STAT_INSERT")) {
    array_push($errors, buildError("No permission"));
    return $errors;
  }

  return _UpdateStats($obj, "INSERT");
}
function UpdateStats($obj) {

  global $session;

  $errors = array();

  if (!$session->isLoggedIn()) {
    array_push($errors, buildError("Not logged in"));
    return $errors;
  }

  if (!$session->hasPermission("STAT_UPDATE")) {
    array_push($errors, buildError("No permission"));
    return $errors;
  }

  return _UpdateStats($obj, "REPLACE");
}
function _UpdateStats($obj, $mode) {

  global $session;

  $db = dbConnect();

  $errors = array();

  $userId = $session->getUserId();

  if (is_null($userId)) {
    array_push($errors, buildError("Unable to determine user"));
    return $errors;
  }

  //
  // Input structure validation
  //

  if (!property_exists($obj, 'stats')) {
    array_push($errors, buildError("Missing mandatory field", "stats"));
  } else if (!is_array($obj->stats)) {
    array_push($errors, buildError("Must be an array", "stats"));
  }

  if (sizeof($errors) > 0) {
    return $errors;
  }

  //
  // Defaults
  //

  $defaultTrainer = NULL;
  $defaultTimestamp = NULL;
  $defaultStat = NULL;

  if (property_exists($obj, 'trainer')) {
    $defaultTrainer = $obj->trainer;
  }

  if (property_exists($obj, 'stat')) {
    $defaultStat = $obj->stat;
  }

  if (property_exists($obj, 'timestamp')) {
    $defaultTimestamp = $obj->timestamp;
  }

  //
  // Build rows
  //

  $trainerIds = array();
  $trainerNames = array();
  $erroredTrainers = array();
  $erroredStats = array();
  $foundStats = array();

  $valueCount = 0;
  $i=0;
  foreach ($obj->stats as $entry) {

    $rowErrors = array();

    $rowTrainer = $defaultTrainer;
    $rowTrainerId = NULL;
    if (property_exists($entry, 'trainer')) {
      $rowTrainer = $entry->trainer;
    }
    if (is_null($rowTrainer)) {
      array_push($rowErrors, buildErrorWithContext("No trainer defined", $entry));
    } else {
      // Validate trainer
      if (array_key_exists($rowTrainer, $trainerIds)) {
        $rowTrainerId = $rowTrainer;
      } else if (array_key_exists($rowTrainer, $trainerNames)) {
        $rowTrainerId = $trainerNames[$rowTrainer];
      } else if (array_key_exists($rowTrainer, $erroredTrainers)) {
        array_push($rowErrors, buildErrorWithContext($erroredTrainers[$rowTrainer], $entry));
      } else {
        $sql = "SELECT id, name, user FROM pogoco_trainer WHERE id = '".$rowTrainer."' OR name = '".$rowTrainer."'";
        $result = $db->query($sql);
        if ($result->num_rows === 1) {
          $trainerResult = $result->fetch_assoc();
          if ($trainerResult['user'] !== $userId) {
            $erroredTrainers[$rowTrainer] = "Cannot edit stats (not owner)";
            array_push($errors, buildErrorWithContext("Cannot edit stats (not owner)", $entry));
          } else {
            $trainerIds[$trainerResult['id']] = $trainerResult['name'];
            $trainerNames[$trainerResult['name']] = $trainerResult['id'];
            $rowTrainerId = $trainerResult['id'];
          }
        } else {
          $erroredTrainers[$rowTrainer] = "Unrecognised trainer";
          array_push($rowErrors, buildErrorWithContext("Unrecognised trainer", $entry));
        }
      }
    }

    $rowStat = $defaultStat;
    if (property_exists($entry, 'stat')) {
      $rowStat = $entry->stat;
    }
    if (is_null($rowStat)) {
      array_push($rowErrors, buildErrorWithContext("No stat defined", $entry));
    } else {
      // Validate stat
      if (array_key_exists($rowStat, $erroredStats)) {
        array_push($rowErrors, buildErrorWithContext($erroredStats[$rowStat], $entry));
      } else if (!array_key_exists($rowStat, $foundStats)) {
        $sql = "SELECT id FROM pogoco_stat WHERE id = '".$rowStat."'";
        $result = $db->query($sql);
        if ($result->num_rows === 1) {
          $foundStats[$rowStat] = 1;
        } else {
          $erroredStats[$rowStat] = "Invalid stat";
          array_push($rowErrors, buildErrorWithContext("Invalid stat", $entry));
        }
      }
    }

    $rowTimestamp = $defaultTimestamp;
    if (property_exists($entry, 'timestamp')) {
      $rowTimestamp = $entry->timestamp;
      error_log($rowTimestamp);
    }
    if (is_null($rowTimestamp)) {
      if ($mode === "INSERT") {
        // Default to current time
        $rowTimestamp = date('Y-m-d G:i:s');
      } else {
        array_push($rowErrors, buildErrorWithContext("No timestamp defined", $entry));
      }
    }

    $value = NULL;
    if (!property_exists($entry, 'value')) {
      array_push($rowErrors, buildErrorWithContext("No value defined", $entry));
    } else {
      if (is_string($entry->value)) {
        if ($entry->value !== "") {
          if (is_numeric($entry->value)) {
            $value = $entry->value;
          } else {
            array_push($rowErrors, buildErrorWithContext("Value is not a number", $entry));
          }
        }
      } else if (is_int($entry->value) || is_float($entry->value)) {
        $value = $entry->value;
      } else {
        array_push($rowErrors, buildErrorWithContext("Value is not a number", $entry));
      }
    }

    if (sizeof($rowErrors) > 0) {
      $errors = array_merge($errors, $rowErrors);
    } else {
      $valueCount++;
      // Perform DB operation
      $sql =
        "$mode INTO pogoco_trainer_stat (trainer, stat, timestamp, value) ".
        "VALUES ('".$rowTrainerId."','".$rowStat."','".$rowTimestamp."','".$value."')";
      $db->query($sql);
      $sqlError = mysqli_error($db);
      if ($sqlError !== "") {
        array_push($errors, buildErrorWithContext("Database error: $sqlError", $entry));
      }
    }

    $i++;
  }

  if (sizeof($errors) === 0 && sizeof($valueCount) === 0) {
    array_push($errors, buildError("No statistics supplied"));
  }

  $db->close();

  return $errors;
}

function DeleteStats($obj) {

  global $session;

  $db = dbConnect();

  $errors = array();

  //
  // Permissioning
  //

  if (!$session->isLoggedIn()) {
    array_push($errors, buildError("Not logged in"));
    return $errors;
  }

  if (!$session->hasPermission("STAT_DELETE")) {
    array_push($errors, buildError("No permission"));
    return $errors;
  }

  $userId = $session->getUserId();

  if (is_null($userId)) {
    array_push($errors, buildError("Unable to determine user"));
    return $errors;
  }

  //
  // Input structure validation
  //

  if (!property_exists($obj, 'stats')) {
    array_push($errors, buildError("Missing mandatory field", "stats"));
  } else if (!is_array($obj->stats)) {
    array_push($errors, buildError("Must be an array", "stats"));
  }

  if (sizeof($errors) > 0) {
    return $errors;
  }

  //
  // Defaults
  //

  $defaultTrainer = NULL;
  $defaultTimestamp = NULL;
  $defaultStat = NULL;

  if (property_exists($obj, 'trainer')) {
    $defaultTrainer = $obj->trainer;
  }

  if (property_exists($obj, 'stat')) {
    $defaultStat = $obj->stat;
  }

  if (property_exists($obj, 'timestamp')) {
    $defaultTimestamp = $obj->timestamp;
  }

  //
  // Build rows
  //

  $values = array();
  $trainerIds = array();
  $trainerNames = array();
  $erroredTrainers = array();
  $erroredStats = array();
  $foundStats = array();

  $valueCount = 0;
  $i=0;
  foreach ($obj->stats as $entry) {

    $rowErrors = array();

    $rowTrainer = $defaultTrainer;
    $rowTrainerId = NULL;
    if (property_exists($entry, 'trainer')) {
      $rowTrainer = $entry->trainer;
    }
    if (is_null($rowTrainer)) {
      array_push($rowErrors, buildErrorWithContext("No trainer defined", $entry));
    } else {
      // Validate trainer
      if (array_key_exists($rowTrainer, $trainerIds)) {
        $rowTrainerId = $rowTrainer;
      } else if (array_key_exists($rowTrainer, $trainerNames)) {
        $rowTrainerId = $trainerNames[$rowTrainer];
      } else if (array_key_exists($rowTrainer, $erroredTrainers)) {
        array_push($rowErrors, buildErrorWithContext($erroredTrainers[$rowTrainer], $entry));
      } else {
        $sql = "SELECT id, name, user FROM pogoco_trainer WHERE id = '".$rowTrainer."' OR name = '".$rowTrainer."'";
        $result = $db->query($sql);
        if ($result->num_rows === 1) {
          $trainerResult = $result->fetch_assoc();
          if ($trainerResult['user'] !== $userId) {
            $erroredTrainers[$rowTrainer] = "Cannot edit stats (not owner)";
            array_push($errors, buildErrorWithContext("Cannot edit stats (not owner)", $entry));
          } else {
            $trainerIds[$trainerResult['id']] = $trainerResult['name'];
            $trainerNames[$trainerResult['name']] = $trainerResult['id'];
            $rowTrainerId = $trainerResult['id'];
          }
        } else {
          $erroredTrainers[$rowTrainer] = "Unrecognised trainer";
          array_push($rowErrors, buildErrorWithContext("Unrecognised trainer", $entry));
        }
      }
    }

    $rowStat = $defaultStat;
    if (property_exists($entry, 'stat')) {
      $rowStat = $entry->stat;
    }
    if (is_null($rowStat)) {
      array_push($rowErrors, buildErrorWithContext("No stat defined", $entry));
    }

    $rowTimestamp = NULL;
    if (property_exists($entry, 'timestamp')) {
      $rowTimestamp = $entry->timestamp;
    } else {
      array_push($rowErrors, buildErrorWithContext("No timestamp defined", $entry));
    }

    if (sizeof($rowErrors) > 0) {
      $sql = "SELECT * FROM pogoco_trainer_stat ".
             "WHERE trainer = '".$rowTrainerId."' ".
               "AND stat = '".$rowStat."' ".
               "AND timestamp = '".$rowTimestamp."'";
      $result = $db->query($sql);
      if ($result->num_rows !== 1) {
        array_push($rowErrors, buildErrorWithContext("No matching stat to delete", $entry));
      }
    }

    if (sizeof($rowErrors) > 0) {
      $errors = array_merge($errors, $rowErrors);
    } else {
      // Perform DB operation
      $valueCount++;
      $sql =
        "DELETE FROM pogoco_trainer_stat ".
        "WHERE trainer='".$rowTrainerId."' ".
          "AND stat='".$rowStat."' ".
          "AND timestamp='".$rowTimestamp."'";
      $db->query($sql);
      $sqlError = mysqli_error($db);
      if ($sqlError !== "") {
        array_push($errors, buildErrorWithContext("Database error: $sqlError", $entry));
      }
    }

    $i++;
  }

  if (sizeof($errors) === 0 && $valueCount === 0) {
    array_push($errors, buildError("No statistics supplied"));
  }

  $db->close();

  return $errors;
}





$MEDAL_ORDER = array("gold", "silver", "bronze", "none");
// Comparision function for 'usort'. Each element of the array must have a rank
// and weight element for it to actually sort them. Else it returns 0.
function CompareStats($a, $b) {
  global $MEDAL_ORDER;
  $ret = 0;
  if (is_array($a) && is_array($b)) {
    $aWeight = 0;
    $bWeight = 0;
    if (array_key_exists('weight', $a) && array_key_exists('weight', $b)) {
      $aWeight = $a['weight'];
      $bWeight = $b['weight'];
    }
    if (array_key_exists('medal', $a) && array_key_exists('medal', $b)) {
      if (strcmp($a['medal'],$b['medal']) !== 0) {
        $aWeight = array_search($a['medal'], $MEDAL_ORDER);
        $bWeight = array_search($b['medal'], $MEDAL_ORDER);
      }
    }
    if ($aWeight === $bWeight) {
      $ret = 0;
    } else if ($aWeight < $bWeight) {
      $ret = -1;
    } else {
      $ret = 1;
    }
  }
  return $ret;
}

?>
