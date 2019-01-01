<?php

require_once dirname(__FILE__).'/lib/session.php';
require_once dirname(__FILE__).'/lib/utils.php';

$session = new UserSession();

function webError($code, $message) {
  header('Content-Type: text');
  http_response_code($code);
  die($message);
}

function webException($exception) {
  webError($exception->getCode(), $exception->getMessage());
}

function webBadRequest($message) {
  webError(400, $message);
}

function webInternalServererror($message) {
  webError(500, $message);
}

?>
