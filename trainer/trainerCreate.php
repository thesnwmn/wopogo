<?php

require_once dirname(__FILE__).'/../app/web.php';

?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1>Create trainer</h1>

<?php if ( !$session->isLoggedIn() ) { ?>

  <p>Must be logged in to create trainers.</p>

<?php } else { ?>

  <form id="trainer-form" onSubmit="return createTrainer()">

    <div class="six columns">
      <label for="name" class="u-full-width">Trainer name</label>
      <input type="text" class="u-full-width" placeholder="Enter trainer name" name="name" required>
    </div>

    <div class="six columns">
      <label for="password" class="u-full-width">Team</label>
      <select name="team" class="u-full-width" required>
        <option value=""></option>
        <option value="INSTINCT">Instinct</option>
        <option value="MYSTIC">Mystic</option>
        <option value="VALOR">Valor</option>
      </select>
    </div>

    <button type="submit" class="u-pull-left button-primary">Create</button>
    <span class="u-pull-left js-form-result block error"></span>
  </form>

<?php } ?>

<script>
function createTrainer() {
  return submitForm(
    '#trainer-form',
    "<?php echo $site_root ?>/app/api/v1/trainer/trainer.php",
    function createSuccess() {
      var nameEle = document.querySelector("#trainer-form input[name]");
      window.location.href = "trainer.php?name=" + nameEle.value;
    }
  );
}
</script>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
