<?php

try {

  require_once dirname(__FILE__).'/../../../api.php';
  require_once dirname(__FILE__).'/../../../lib/trainers.php';

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!isset($_GET['trainer'])) {
      apiBadRequest("Must supply 'trainer'");
    } else {
      apiSuccess(GetTrainerStats(false, $_GET['trainer']));
    }

  } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    apiBadRequest("Invalid request method: '".$_SERVER['REQUEST_METHOD']."'");
  }
} catch (Exception $e) {
  apiException($e);
}

?>
