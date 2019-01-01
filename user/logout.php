<?php require_once dirname(__FILE__).'/../app/web.php'; ?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1>Log out</h1>

<?php if ( !$session->isLoggedIn() ) { ?>

  <p>Already logged out.</p>

<?php } else { ?>

  <div class="section">
    <form id="logout-form" onSubmit="return submitForm('#logout-form', '<?php echo $site_root ?>/app/api/v1/logout.php', '<?php echo $site_root ?>/index.php')">
      <div class="row">
        <button type="submit" class="u-pull-left button-primary">Log out</button>
        <span class="u-pull-left block js-form-result error"></span>
      </div>
    </form>
  </div>

<?php } ?>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
