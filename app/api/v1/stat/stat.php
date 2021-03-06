<?php

try {
  require_once dirname(__FILE__).'/../../../api.php';
  require_once dirname(__FILE__).'/../../../lib/stats.php';

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!isset($_GET['stat'])) {
      apiBadRequest("Must supply 'stat'");
    } else {
      apiSuccess(GetStat($_GET['stat']));
    }

  } else {
    apiBadRequest("Invalid request method: '".$_SERVER['REQUEST_METHOD']."'");
  }

} catch (Exception $e) {
  apiException($e);
}

?>
