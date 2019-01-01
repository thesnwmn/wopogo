<?php require_once dirname(__FILE__).'/../app/web.php'; ?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1>Register</h1>

<?php if ( $session->isLoggedIn() ) { ?>

  <p>Cannot register, already logged in.</p>

<?php } else { ?>

    <div class="row">
      <p>All information about your user is held and used only by this site.
        Your username and email address will never be displayed to anyone but
        yourself when logged in. The only emails you will retrieve are any
        directly requested in relation to account recovery.</p>
    </div>

    <form id="register-form" onSubmit="return submitForm('#register-form', '<?php echo $site_root ?>/app/api/v1/register.php', 'registerSuccess.php')">

      <div class="row">
        <div class="six columns">
          <label for="username" class="u-full-width">Username</label>
          <input type="text" class="u-full-width" placeholder="Enter username" name="username" required>
        </div>
        <div class="six columns">
          <label for="password" class="u-full-width">Password</label>
          <input type="password" class="u-full-width" placeholder="Enter password" name="password" required>
        </div>
      </div>

      <div class="row">
        <div class="twelve columns">
          <label for="email_address" class="u-full-width">Email address</label>
          <input type="email" class="u-full-width" placeholder="Enter email address" name="email_address" required>
        </div>
      </div>

      <div class="row">
        <button type="submit" class="u-pull-left button-primary">Register</button>
        <span class="u-pull-left block js-form-result error"></span>
      </div>

    </form>

<?php } ?>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
