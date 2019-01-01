<?php

try {
  require_once dirname(__FILE__).'/../../api.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiBadRequest("Only POST supported");
  }

  if ($session->isLoggedIn()) {
    apiBadRequest("Already logged in");
  } else {

    $inputJSON = file_get_contents('php://input');
    $json = json_decode($inputJSON, FALSE);

    $errors = array();

    if (!property_exists($json, "username")) {
      array_push($errors, buildError("Missing mandatory field", "username"));
    }

    if (!property_exists($json, "password")) {
      array_push($errors, buildError("Missing mandatory field", "password"));
    }

    if (sizeof($errors) !== 0) {
      apiBadRequest($errors);
    }

    $errors = $session->login($json->username, $json->password);
    if (sizeof($errors) === 0) {
      apiSuccess("Logged in");
    } else {
      apiBadRequest($errors);
    }
  }

} catch (Exception $e) {
  apiException($e);
}

?>
