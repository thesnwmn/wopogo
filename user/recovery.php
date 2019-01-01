<?php require_once dirname(__FILE__).'/../app/web.php'; ?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1>Recovery</h1>

<?php if ( $session->isLoggedIn() ) { ?>

  <p>Cannot recover account whilst logged in.</p>

<?php } else { ?>

  <div class="row">
    <h3>Password reset</h3>
  </div>

  <div class="row">
    <form id="reset-form" onSubmit="return resetPassword()">

      <div class="row">
        <div class="six columns">
          <label for="username" class="u-full-width">Username</label>
          <input type="text" class="u-full-width" placeholder="Enter username" name="username" required>
        </div>
      </div>

      <div class="row">
        <button type="submit" class="u-pull-left button-primary">Reset password</button>
        <span class="u-pull-left block js-form-result error"></span>
      </div>

    </form>
  </div>

  <div class="row">
    <h3>Retrieve username</h3>
  </div>

  <div class="row">
    <form id="retrieve-form" onSubmit="return retrieveUsername()">

      <div class="row">
        <div class="six columns">
          <label for="username" class="u-full-width">Email address</label>
          <input type="text" class="u-full-width" placeholder="Enter email address" name="email_address" required>
        </div>
      </div>

      <div class="row">
        <button type="submit" class="u-pull-left button-primary">Retrieve username</button>
        <span class="u-pull-left block js-form-result error"></span>
      </div>

    </form>
  </div>

<?php } ?>

<script>
function retrieveUsername() {
  return submitForm(
    '#retrieve-form',
    '<?php echo $site_root ?>/app/api/v1/user/retrieveUsername.php',
    '<?php echo $site_root ?>/user/recoverySuccess.php?type=1'
  );
}
function resetPassword() {
  return submitForm(
    '#reset-form',
    '<?php echo $site_root ?>/app/api/v1/user/resetPassword.php',
    '<?php echo $site_root ?>/user/recoverySuccess.php?type=2'
  );
}
</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
