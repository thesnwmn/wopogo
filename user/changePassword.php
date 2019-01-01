<?php require_once dirname(__FILE__).'/../app/web.php'; ?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1>Change password</h1>

<?php if ( !$session->isLoggedIn() ) { ?>

  <p>Not logged in. Cannot change password.</p>

<?php } else { ?>

  <div class="section">

    <form id="change-form" onSubmit="return changePassword()">

      <div class="row">
        <div class="twelve columns">
          <label for="psw" class="u-full-width">Current password</label>
          <input type="password" class="u-full-width" placeholder="Enter current password" name="current_password" required>
        </div>
      </div>

      <div class="row">
        <div class="six columns">
          <label for="psw" class="u-full-width">New password</label>
          <input type="password" class="u-full-width" placeholder="Enter new password" name="new_password" required>
        </div>

        <div class="six columns">
          <label for="psw" class="u-full-width">Repeat new password</label>
          <input type="password" class="u-full-width" placeholder="Repeat new password" name="repeat_password" required>
        </div>
      </div>

      <div class="row">
        <button type="submit" class="u-pull-left button-primary">Change Password</button>
        <span class="u-pull-left block js-form-result error"></span>
      </div>

    </form>
  </div>

<?php } ?>

<script>
function changePassword() {
  return submitForm(
    '#change-form',
    '<?php echo $site_root ?>/app/api/v1/user/changePassword.php',
    'profile.php');
}
</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
