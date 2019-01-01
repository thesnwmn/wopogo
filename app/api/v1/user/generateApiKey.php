<?php

try {
  require_once dirname(__FILE__).'/../../../api.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiBadRequest("Only POST supported");
  }

  $errors = $session->generateApiKey($key);
  if (sizeof($errors) === 0) {
    apiSuccess($key);
  } else {
    apiBadRequest($errors);
  }

} catch (Exception $e) {
  apiException($e);
}

?>
