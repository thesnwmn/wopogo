<?php require_once dirname(__FILE__).'/../app/web.php'; ?>
<?php require_once dirname(__FILE__).'/../app/lib/trainers.php'; ?>

<?php
try {
  $trainers = getTrainersForUserByName($session->getUsername());
} catch (Exception $e) {
  webException($e);
}
?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1>Profile</h1>

<?php if (!$session->isLoggedIn()) { ?>

  <p>Not logged in</p>

<?php } else { ?>

  <p>Welcome to your profile <?php echo $session->getUsername(); ?></p>

  <div class="section">

    <h2>Trainers</h2>

    <table class="u-full-width">
      <thead>
        <tr>
          <th width="50%">Name</th>
          <th width="50%">Updated</th>
          <th class="center">Team</th>
      </thead>
      <tbody>
        <?php foreach ($trainers['trainers'] as $trainer) { ?>
          <tr>
            <td>
              <a href="<?php echo $site_root ?>/trainer/trainer.php?name=<?php echo $trainer['name']; ?>">
                <?php echo $trainer['name']; ?>
              </a>
            </td>
            <td><?=displayDate($trainer['last_update'])?></td>
            <td class="center"><div class="icon icon-img-team-<?php echo $trainer['team']; ?>"></div></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
    <a class="button" href="<?php echo $site_root ?>/trainer/trainerCreate.php">Create trainer</a>
  </div>

  <div class="row">
    <h2>Account</h2>
  </div>
  <div class="row">
    <a class="button" href="changePassword.php">Change password</a>
    <a class="button" href="#" onclick="generateApiKey()">Generate API Key</a>
  </div>
  <div class="row">
    <span id="account-message"></span>
  </div>

<?php } ?>

<script>
function generateApiKey() {
  httpPostAsync(
      '<?php echo $site_root ?>/app/api/v1/user/generateApiKey.php',
      "",
      function(result) {
        document.querySelector("#account-message").textContent =
          "New API Key: " + result;
      },
      function() {
        document.querySelector("#account-message").textContent =
          "Failed to generate new API Key";
      }
    );
}
</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
