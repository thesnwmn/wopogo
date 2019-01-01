<?php

try {
  require_once dirname(__FILE__).'/../../../api.php';
  require_once dirname(__FILE__).'/../../../lib/stats.php';

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    apiSuccess(GetStatCategories(false));
  } else {
    apiBadRequest("Invalid request method: '".$_SERVER['REQUEST_METHOD']."'");
  }

} catch (Exception $e) {
  apiException($e);
}

?>
