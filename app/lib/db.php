<?php

require_once dirname(__FILE__).'/../config.php';

function dbConnect() {

  global $db_server, $db_username, $db_password, $db_database;

  $conn = new mysqli($db_server, $db_username, $db_password, $db_database);
  if ($conn->connect_error) {
      throw new Exception("Connection failed: " . $conn->connect_error, 500);
  }

  $conn->query("SET timezone = '+0:00'");

  return $conn;
}

function dbQuery($sql, $conn = NULL) {

  $cleanup = false;
  if ($conn === NULL) {
    $conn = dbConnect();
  }

  $result = $conn->query($sql);
  $error = mysqli_error($conn);

  if ($cleanup) {
    $conn->close();
  }

  if ($error !== "") {
    throw new Exception("DbError: $error", 400);
  }

  return $result;
}

?>
