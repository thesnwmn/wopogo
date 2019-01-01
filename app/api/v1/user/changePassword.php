<?php

try {
  require_once dirname(__FILE__).'/../../../api.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiBadRequest("Only POST supported");
  }

  $inputJSON = file_get_contents('php://input');
  $json = json_decode($inputJSON, FALSE);

  $errors = array();

  if (!property_exists($json, "current_password")) {
    array_push($errors, buildError("Missing mandatory field", "current_password"));
  }

  if (!property_exists($json, "new_password")) {
    array_push($errors, buildError("Missing mandatory field", "new_password"));
  }

  if (!property_exists($json, "repeat_password")) {
    array_push($errors, buildError("Missing mandatory field", "repeat_password"));
  }

  if (sizeof($errors) !== 0) {
    apiBadRequest($errors);
  }

  $errors = $session->changePassword($json->current_password, $json->new_password, $json->repeat_password);
  if (sizeof($errors) === 0) {
    apiSuccess("Password changed successfully");
  } else {
    apiBadRequest($errors);
  }

} catch (Exception $e) {
  apiException($e);
}

?>
