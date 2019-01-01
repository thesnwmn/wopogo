<html>
<head>
  <title>WoPoGo - Pokemon Community</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="<?php echo $site_root ?>/res/style/skeleton/normalize.css">
  <link rel="stylesheet" type="text/css" href="<?php echo $site_root ?>/res/style/skeleton/skeleton.css">
  <link rel="stylesheet" type="text/css" href="<?php echo $site_root ?>/res/style/core.css">
  <script src="<?php echo $site_root ?>/res/js/core.js?1"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.min.js"></script>
</head>
<body>
<div class="header">
  <div class="container">
    <div class="banner">
      <a href="<?php echo $site_root ?>/index.php">Woking Pokemon Go</a>
    </div>
    <div class="nav u-pull-left">
        <a href="<?php echo $site_root ?>/trainer/trainers.php">Trainers</a
        ><a href="<?php echo $site_root ?>/stat/stats.php">Leaderboards</a>
      </div>
      <div class="nav u-pull-right">
        <?php if ($session->isLoggedIn()) { ?>
          <a href="<?php echo $site_root ?>/user/profile.php">Profile</a
          ><a href="<?php echo $site_root ?>/user/logout.php">Logout</a>
        <?php } else { ?>
          <a href="<?php echo $site_root ?>/user/register.php">Register</a
          ><a href="<?php echo $site_root ?>/user/login.php">Login</a>
        <?php } ?>
    </div>
  </div>
</div>
<div class="page">
  <div class="container">
