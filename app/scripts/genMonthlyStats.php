<?php

require_once dirname(__FILE__).'/../lib/db.php';

$db = dbConnect();

dbQuery(
  "SET @current = UTC_TIMESTAMP();",
  $db);
dbQuery(
  "DELETE FROM pogoco_trainer_stat_monthly WHERE ".
  "month = MONTH(@current) AND year = YEAR(@current)",
  $db);
dbQuery(
  "REPLACE INTO pogoco_trainer_stat_monthly (year, month, stat, trainer, value) ".
  "SELECT * ".
  "FROM ( ".
    "SELECT YEAR(@current) as year, MONTH(@current) as month, stat, trainer, MAX(value) - MIN(value) as value ".
	  "FROM pogoco_trainer_stat ".
	  "WHERE MONTH(timestamp) = MONTH(@current) AND YEAR(timestamp) = YEAR(@current) ".
	  "GROUP BY stat, trainer) as data ".
  "WHERE value > 0", $db);

dbQuery(
  "SET @previous = UTC_TIMESTAMP() - INTERVAL 1 MONTH;",
  $db);
dbQuery(
  "DELETE FROM pogoco_trainer_stat_monthly WHERE ".
  "month = MONTH(@previous) AND year = YEAR(@previous)",
  $db);
dbQuery(
  "REPLACE INTO pogoco_trainer_stat_monthly (year, month, stat, trainer, value) ".
  "SELECT * ".
  "FROM ( ".
    "SELECT YEAR(@previous) as year, MONTH(@previous) as month, stat, trainer, MAX(value) - MIN(value) as value ".
	  "FROM pogoco_trainer_stat ".
	  "WHERE MONTH(timestamp) = MONTH(@previous) AND YEAR(timestamp) = YEAR(@previous) ".
	  "GROUP BY stat, trainer) as data ".
  "WHERE value > 0", $db);

$db->close();

?>
