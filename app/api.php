<?php

require_once dirname(__FILE__).'/lib/session.php';
require_once dirname(__FILE__).'/lib/utils.php';

$session = new UserSession();

function apiSuccess($obj) {
  header('Content-Type: application/json');
  http_response_code(200);
  die(json_encode($obj));
}

function apiError($code, $message) {
  header('Content-Type: text');
  http_response_code($code);
  $obj = array();
  if (is_array($message)) {
    $obj['errors'] = $message;
  } else {
    $obj['errors'] = array();
    if (is_string($message)) {
      array_push($obj['errors'], buildError($message));
    } else {
      array_push($obj['errors'], $message);
    }
  }
  die(json_encode($obj));
}

function apiException($exception) {
  apiError($exception->getCode(), $exception->getMessage());
}

function apiBadRequest($message) {
  apiError(400, $message);
}

function apiInternalServererror($message) {
  apiError(500, $message);
}

?>
