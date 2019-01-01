<?php

try {
  require_once dirname(__FILE__).'/../../../api.php';
  require_once dirname(__FILE__).'/../../../lib/stats.php';

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['category'])) {
      apiSuccess(GetStatsForCategory(false, $_GET['category']));
    } else {
      apiSuccess(GetStats(false));
    }

  } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inputJSON = file_get_contents('php://input');
    $json = json_decode($inputJSON, FALSE);

    $errors = InsertStats($json);
    if (sizeof($errors) > 0) {
      apiBadRequest($errors);
    } else {
      apiSuccess("Inserted stats");
    }

  } else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    $inputJSON = file_get_contents('php://input');
    $json = json_decode($inputJSON, FALSE);

    $errors = UpdateStats($json);
    if (sizeof($errors) > 0) {
      apiBadRequest($errors);
    } else {
      apiSuccess("Updated stats");
    }

  } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    $inputJSON = file_get_contents('php://input');
    $json = json_decode($inputJSON, FALSE);

    $errors = DeleteStats($json);
    if (sizeof($errors) > 0) {
      apiBadRequest($errors);
    } else {
      apiSuccess("Deleted stats");
    }

  } else {
    apiBadRequest("Invalid request method: '".$_SERVER['REQUEST_METHOD']."'");
  }

} catch (Exception $e) {
  apiException($e);
}

?>
