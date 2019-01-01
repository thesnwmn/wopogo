<?php

try {
  require_once dirname(__FILE__).'/../../../api.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiBadRequest("Only POST supported");
  }

  $inputJSON = file_get_contents('php://input');
  $json = json_decode($inputJSON, FALSE);

  $errors = array();

  if (!property_exists($json, "email_address")) {
    array_push($errors, buildError("Missing mandatory field", "email_address"));
  }

  if (sizeof($errors) !== 0) {
    apiBadRequest($errors);
  }

  $errors = $session->retrieveUsername($json->email_address);
  if (sizeof($errors) === 0) {
    apiSuccess("Username sent successfully");
  } else {
    apiBadRequest($errors);
  }

} catch (Exception $e) {
  apiException($e);
}

?>
