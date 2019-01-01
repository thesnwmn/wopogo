<?php

try {
  require_once dirname(__FILE__).'/../../api.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiBadRequest("Only POST supported");
  }

  $session->logout();

  apiSuccess("Logged out");

} catch (Exception $e) {
  apiException($e);
}

?>
