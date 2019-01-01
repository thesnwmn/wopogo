<?php
require_once dirname(__FILE__).'/app/web.php';
?>

<?php require_once dirname(__FILE__).'/res/include/top.php'; ?>

<div class="row">
  <h1>Change log</h1>
</div>

<div class="row">
  <h3>v0.8 - April 25th, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Fixed form submission in Internet Explorer.</li>
    <li>Fixed issue preventing Hotmail users recieving email.</li>
    <li>Added monthly stat generation, no display yet.</li>
  </ul>
</div>

<div class="row">
  <h3>v0.7 - April 19th, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Tidied up API structure.</li>
    <li>Added start of user permissioning. At the moment the only "roles" are
      whether the user is logged in via a session or using an API key,
      with API key users only able to insert new stat values.</li>
    <li>Added API docs.</li>
  </ul>
</div>

<div class="row">
  <h3>v0.6 - April 12th, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Stat history interaction now supported. Add, delete and modify entries
      from the stat history page.</li>
    <li>Laid groundwork for interaction with the API using an API key rather
      than PHP based sessions and cookies. An API key can be generated from the
      user's profile page.</li>
  </ul>
</div>

<div class="row">
  <h3>v0.5.1 - April 11th, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Display dates and times in user specific timezone. Defaults to
      'Europe/London' when not set for the user (which it cannot be at this
      time).</li>
    <li>Fixed stat history not displaying stat name in title</li>
  </ul>
</div>
<div class="row">
  <h3>v0.5 - April 7th, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Display last update date on trainer list</li>
    <li>Display last update date/time on trainer page</li>
    <li>Show list of most recently updated trainers on home page</li>
    <li>Format timestamps on historical stats display as "DD/MM/YYYY HH:MM:SS"</li>
  </ul>
</div>

<div class="row">
  <h3>v0.4.2 - April 5th, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Fix ordering of medals on trainer page (broken in 0.4)</li>
  </ul>
</div>

<div class="row">
  <h3>v0.4.1 - April 4th, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Fix type medal chart on home page (broken in 0.4)</li>
  </ul>
</div>

<div class="row">
  <h3>v0.4 - April 4th, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Number formatting on leaderboards page</li>
    <li>Trainer page enhancements:
      <ul>
        <li>Show ranks on trainer page</li>
        <li>Add leaderboard and history buttons to experience and statistics on trainer page</li>
      </ul>
    </li>
  </ul>
</div>

<div class="row">
  <h3>v0.3 - April 3rd, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Ranks handle multiple users with the same value</li>
    <li>Leaderboard now in game stat order (probably to be reverted to alphabetical at some point)</li>
  </ul>
</div>

<div class="row">
  <h3>v0.2 - April 2nd, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Added change log page (this one)</li>
    <li>Added new statistics:
      <ul>
        <li>Gold gyms</li>
        <li>Gym badges</li>
        <li>Max hours defending a single gym</li>
        <li>Unique 100% IV Pokemon</li>
      </ul
    </li>
    <li>Display trainer medal view and entry in game order</li>
  </ul>
</div>

<div class="row">
  <h3>v0.1 - April 1st, 2018</h3>
</div>
<div class="row">
  <ul>
    <li>Initial release</li>
  </ul>
</div>

<?php require_once dirname(__FILE__).'/res/include/tail.php'; ?>
