<?php require_once dirname(__FILE__).'/../app/web.php'; ?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1>Log in</h1>

<?php if ( $session->isLoggedIn() ) { ?>

  <p>Already logged in.</p>

<?php } else { ?>

  <div class="section">
    <form id="login-form" onSubmit="return submitForm('#login-form', '<?php echo $site_root ?>/app/api/v1/login.php', 'profile.php')">

      <div class="row">
        <div class="six columns">
          <label for="username" class="u-full-width">Username</label>
          <input type="text" class="u-full-width" placeholder="Enter username" name="username" required>
        </div>
        <div class="six columns">
          <label for="psw" class="u-full-width">Password</label>
          <input type="password" class="u-full-width" placeholder="Enter Password" name="password" required>
        </div>
      </div>

      <div class="row">
        <button type="submit" class="u-pull-left button-primary">Login</button>
        <span class="u-pull-left js-form-result block error"></span>
      </div>

    </form>
  </div>

  <div class="section">
    <p>Forgotten your username or password? Go <a href="recovery.php">here</a></p>

  </div>

<?php } ?>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
