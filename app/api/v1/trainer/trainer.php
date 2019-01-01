<?php

try {

  require_once dirname(__FILE__).'/../../../api.php';
  require_once dirname(__FILE__).'/../../../lib/trainers.php';

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!isset($_GET['trainer'])) {
      apiBadRequest("Must supply 'trainer'");
    } else {
      apiSuccess(GetTrainer($_GET['trainer']));
    }

  } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inputJSON = file_get_contents('php://input');
    $json = json_decode($inputJSON, FALSE);

    $errors = createTrainer($json);
    if (sizeof($errors) > 0) {
      apiBadRequest($errors);
    } else {
      apiSuccess("Created trainer");
    }

  } else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    apiBadRequest("Not implemented");

  } else {
    apiBadRequest("Invalid request method: '".$_SERVER['REQUEST_METHOD']."'");
  }
} catch (Exception $e) {
  apiException($e);
}

?>
