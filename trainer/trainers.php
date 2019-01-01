<?php

require_once dirname(__FILE__).'/../app/web.php';
require_once dirname(__FILE__).'/../app/lib/trainers.php';

try {
  $trainers = getTrainers();
} catch (Exception $e) {
  webException($e);
}
?>

<?php require_once dirname(__FILE__).'/../res/include/top.php'; ?>

<h1>Trainers</h1>

<div class="row">
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
</div>

<?php require_once dirname(__FILE__).'/../res/include/tail.php'; ?>
