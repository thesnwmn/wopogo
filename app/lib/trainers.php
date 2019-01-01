<?php

require_once dirname(__FILE__).'/db.php';


function GetTrainer($trainer) {

  global $session;

  $db = dbConnect();

  $sql =
    "SELECT pt.id, pt.name, pt.team, pt.user, latest.timestamp as last_update ".
    "FROM pogoco_trainer pt ".
          "LEFT JOIN ( ".
            "SELECT *".
            "FROM pogoco_trainer_stat pts ".
          ") AS latest ON latest.trainer = pt.id ".
    "WHERE id = '$trainer' OR name = '$trainer' ".
    "ORDER BY latest.timestamp DESC ".
    "LIMIT 1 ";

  $result = $db->query($sql);
  if ($result->num_rows == 0) {
    throw new Exception("No matching trainer", 400);
  } else if ($result->num_rows > 1) {
    throw new Exception("Multiple matching trainers", 500);
  } else {
    $obj = $result->fetch_assoc();

    $obj['flags'] = array();
    $obj['flags']['editable'] = false;

    if ($session->isLoggedIn() &&
        !is_null($session->getUserId()) &&
        $obj['user'] === $session->getUserId()) {

        $obj['flags']['editable'] = true;
    }

    unset($obj['user']);
  }

  $db->close();

  return $obj;
}


function GetTrainerStats($map, $trainer) {

  $obj = array();

  $db = dbConnect();

  $sql =
    "SELECT ".
        "pgl.stat, ".
        "pgl.position as rank, ".
        "pgl.value, ".
        "ps.* ".
    "FROM ".
        "pogoco_gen_leaderboard pgl, ".
        "pogoco_stat ps, ".
        "pogoco_trainer pt ".
    "WHERE pgl.trainer = pt.id ".
      "AND (pt.id = '$trainer' OR pt.name = '$trainer') ".
      "AND ps.id = pgl.stat ";

    $result = $db->query($sql);
    $obj['stats'] = array();
    if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {

        $details = array();

        $details['value'] = $row['value'];
        $details['rank'] = $row['rank'];

        if (!is_null($row['gold_threshold']) && $row['value'] >= $row['gold_threshold']) {
          $details['medal'] = "gold";
        } else if (!is_null($row['silver_threshold']) && $row['value'] >= $row['silver_threshold']) {
          $details['medal'] = "silver";
        } else if (!is_null($row['bronze_threshold']) && $row['value'] >= $row['bronze_threshold']) {
          $details['medal'] = "bronze";
        }

        if ($map) {
          $obj['stats'][$row['stat']] = $details;
        } else {
          $details['stat'] = $row['stat'];
          array_push($obj['stats'], $details);
        }
      }
    }

    $db->close();

    return $obj;
}
function GetTrainerStat($trainer, $stat) {

  $obj = array();

  $db = dbConnect();

  $sql =
    "SELECT ".
        "pgl.stat, ".
        "pgl.position as rank, ".
        "pgl.value, ".
        "ps.* ".
    "FROM ".
        "pogoco_gen_leaderboard pgl, ".
        "pogoco_stat ps, ".
        "pogoco_trainer pt ".
    "WHERE pgl.trainer = pt.id ".
      "AND (pt.id = '$trainer' OR pt.name = '$trainer') ".
      "AND pgl.stat = '$stat' ".
      "AND ps.id = pgl.stat ";

    $result = $db->query($sql);
    if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {

        $obj['value'] = $row['value'];
        $obj['rank'] = $row['rank'];

        if (!is_null($row['gold_threshold']) && $row['value'] >= $row['gold_threshold']) {
          $obj['medal'] = "gold";
        } else if (!is_null($row['silver_threshold']) && $row['value'] >= $row['silver_threshold']) {
          $obj['medal'] = "silver";
        } else if (!is_null($row['bronze_threshold']) && $row['value'] >= $row['bronze_threshold']) {
          $obj['medal'] = "bronze";
        }
      }
    }

    $db->close();

    return $obj;
}
function GetTrainerLevel($trainer) {

  $obj = array();

  $db = dbConnect();

  $sql =
    "SELECT ".
        "pgl.stat, ".
        "pgl.position as rank, ".
        "pgl.value, ".
        "ps.* ".
    "FROM ".
        "pogoco_gen_leaderboard pgl, ".
        "pogoco_stat ps, ".
        "pogoco_trainer pt ".
    "WHERE pgl.trainer = pt.id ".
      "AND (pt.id = '$trainer' OR pt.name = '$trainer') ".
      "AND pgl.stat = 'xp' ".
      "AND ps.id = pgl.stat ";

  $result = $db->query($sql);

  print_r($result);
  $xp = 0;

  if ($result->num_rows === 1) {
    $xpRow = $result->fetch_assoc();
    $xp = $xpRow['value'];
    $obj['rank'] = $xpRow['rank'];
  }

  $obj['xp'] = $xp;

  // Level
  $level = 1;
  $result = $db->query(
    "SELECT COUNT(level) as level FROM `pogoco_ref_trainer_xp` WHERE xp_total <= ".$xp);
  if ($result->num_rows == 1) {
    $level = $result->fetch_assoc()['level'];
  }
  $obj['level'] = $level;

  // %age way to 40
  $maxResult = $db->query(
    "SELECT xp_total FROM `pogoco_ref_trainer_xp` ORDER BY xp_total DESC LIMIT 1");
  if ($maxResult->num_rows == 1) {
    $maxXP = $maxResult->fetch_assoc()['xp_total'];
    $obj['max']['required'] = $maxXP;
    if ($xp > $maxXP) {
      $obj['max']['progress'] = 100;
    } else {
      $obj['max']['progress'] = ($xp / $maxXP)*100;

      // %age way to next level
      $nextLevel = $level + 1;
      $nextResult = $db->query(
        "SELECT xp, xp_total FROM pogoco_ref_trainer_xp WHERE level = ".$nextLevel." LIMIT 1");
      if ($nextResult->num_rows == 1) {
        $nextRow = $nextResult->fetch_assoc();

        $nextXP = $nextRow['xp'];
        $nextTotal = $nextRow['xp_total'];
        $currentTotal = $nextTotal - $nextXP;
        $nextEarnt = $xp - $currentTotal;
        $nextProgress = ($nextEarnt/$nextXP)*100;

        $obj['next']['earnt'] = $nextEarnt;
        $obj['next']['required'] = $nextXP;
        $obj['next']['total'] = $nextTotal;
        $obj['next']['progress'] = $nextProgress;
      }
    }
  }

  $db->close();

  return $obj;
}


















function GetTrainers() {
  return _GetTrainers(
    "SELECT pt.id, pt.name, pt.team, pt.user, pglu.timestamp as last_update ".
    "FROM pogoco_trainer pt, pogoco_gen_last_updated pglu ".
    "WHERE pglu.trainer = pt.id ".
    "ORDER BY name");
}
function GetLastUpdatedTrainers($limit = 20) {
  return _GetTrainers(
    "SELECT pglu.trainer as id, pglu.timestamp as last_update, pt.name, pt.team, pt.user ".
    "FROM pogoco_gen_last_updated pglu, pogoco_trainer pt ".
    "WHERE pt.id = pglu.trainer ".
    "ORDER BY pglu.timestamp DESC ".
    "LIMIT $limit"
  );
}

function GetTrainersForUserByName($username) {
  return _getTrainers(
    "SELECT pt.id, pt.name, pt.team, pt.user, pglu.timestamp as last_update ".
    "FROM pogoco_trainer pt, pogoco_user pu, pogoco_gen_last_updated pglu ".
    "WHERE pt.user = pu.id AND pu.username = '".$username."' ".
    "  AND pglu.trainer = pt.id ".
    "ORDER BY name");
}
function _GetTrainers($sql) {

  global $session;

  $obj = array();

  $conn = dbConnect();

  $result = $conn->query($sql);

  $obj['trainers'] = array();

  if ($result->num_rows > 0) {

      while($row = $result->fetch_assoc()) {

        $rowObj = array();
        $rowObj['flags'] = array();
        $rowObj['flags']['editable'] = false;

        if ($session->isLoggedIn() &&
            !is_null($session->getUserId()) &&
            $row['user'] === $session->getUserId()) {

            $rowObj['flags']['editable'] = true;
        }

        $rowObj = $row;
        unset($rowObj['user']);

        array_push($obj['trainers'], $rowObj);
      }
  }

  $conn->close();

  return $obj;
}


function CreateTrainer($obj) {

  global $session;

  $errors = array();

  //
  // Permissioning
  //

  if (!$session->isLoggedIn()) {
    array_push($errors, buildError("Not logged in"));
    return $errors;
  }

  if (!$session->hasPermission("TRAINER_CREATE")) {
    array_push($errors, buildError("No permission"));
    return $errors;
  }

  //
  // Input validation
  //

  if (property_exists($obj, 'id')) {
    array_push($errors, buildError("Cannot supply trainer 'ID' during creation", "id"));
  }

  if (!property_exists($obj, 'name')) {
    array_push($errors, buildError("Missing mandatory field", "name"));
  }

  if (!property_exists($obj, 'team')) {
    array_push($errors, buildError("Missing mandatory field", "team"));
  }

  /* Use for admin based creation
  if (!property_exists($obj, 'user')) {
    array_push($errors, buildError("Missing mandatory field", "user"));
  }*/

  if (sizeof($errors) > 0) {
    return $errors;
  }

  $conn = dbConnect();

  //
  // Database validation
  //

  // No clashing trainer on name
  $sql = "SELECT * FROM pogoco_trainer WHERE name = '".$obj->name."'";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    array_push($errors, buildError("Trainer already exists", "name"));
  }

  $userId = $session->getUserId();

  if (is_null($userId)) {
    array_push($errors, buildError("Unable to determine user"));
  }

  /* Use for admin based creation
  // Lookup user by ID
  if (is_null($userId)) {
    $sql = "SELECT id FROM pogoco_user WHERE id = '".$obj->user."'";
    $result = $conn->query($sql);
    if ($result->num_rows === 1) {
      $userId = $result->fetch_assoc()['id'];
    }
  }

  // Lookup user by name
  if (is_null($userId)) {
    $sql = "SELECT id FROM pogoco_user WHERE username = '".$obj->user."'";
    $result = $conn->query($sql);
    if ($result->num_rows === 1) {
      $userId = $result->fetch_assoc()['id'];
    }
  }
  */

  if (sizeof($errors) > 0) {
    return $errors;
  }

  //
  // Create
  //

  $fields = array("id", "name", "team", "user");
  $values = array("UUID()", "'".$obj->name."'", "'".$obj->team."'", "'".$userId."'");

  $sql = "INSERT INTO pogoco_trainer (" . implode(",", $fields) . ") VALUES (" . implode(",", $values) .")";
  $result = $conn->query($sql);

  $sqlError = mysqli_error($conn);
  if ($sqlError !== "") {
    array_push($errors, buildError($sqlError));
  }

  return $errors;
}

?>
