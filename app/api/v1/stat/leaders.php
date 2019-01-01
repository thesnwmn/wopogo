<?php

try {
  require_once dirname(__FILE__).'/../../../api.php';
  require_once dirname(__FILE__).'/../../../lib/stats.php';

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['stat'])) {
      apiSuccess(GetStatLeaders($_GET['stat']));
    } else if (isset($_GET['category'])) {
      apiSuccess(GetStatsLeadersForCategory(false, $_GET['category']));
    } else {
      apiSuccess(GetStatsLeaders(false));
    }

  } else {
    apiBadRequest("Invalid request method: '".$_SERVER['REQUEST_METHOD']."'");
  }

} catch (Exception $e) {
  apiException($e);
}

?>
