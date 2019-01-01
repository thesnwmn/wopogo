<?php

require_once dirname(__FILE__).'/db.php';

class UserSession {

  private $NO_PERMISSIONS = array();

  private $SESSION_PERMISSIONS = array(
    "TRAINER_CREATE" => 1,
    "STAT_INSERT" => 1,
    "STAT_UPDATE" => 1,
    "STAT_DELETE" => 1
  );

  private $API_PERMISSIONS = array(
    "STAT_INSERT" => 1
  );

  private $userRow = NULL;
  private $permissions = array();

  public function __construct() {

    if (array_key_exists('HTTP_X_API_KEY', $_SERVER)) {

      $keyHash = hash('sha256', $_SERVER['HTTP_X_API_KEY']);
      $db = dbConnect();
      $result = $db->query(
        "SELECT id, username, email_address, password_hash, role, timezone ".
        "FROM pogoco_user, pogoco_user_api ".
        "WHERE pogoco_user_api.api_key_hash = '$keyHash' AND pogoco_user.id = pogoco_user_api.user");
      $sqlError = mysqli_error($db);
      if ($sqlError === "" && $result->num_rows === 1) {
        $this->userRow = $result->fetch_assoc();
        $this->permissions = $this->API_PERMISSIONS;
      }
      $db->close();

    } else {

      session_start();

      // Session management:
      // https://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes
      // TODO consider cookie expiry

      if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1600)) {
          // last request was more than 30 minutes ago
          session_unset();     // unset $_SESSION variable for the run-time
          session_destroy();   // destroy session data in storage
      }
      $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

      if (!isset($_SESSION['CREATED'])) {
          $_SESSION['CREATED'] = time();
      } else if (time() - $_SESSION['CREATED'] > 1800) {
          // session started more than 30 minutes ago
          session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID
          $_SESSION['CREATED'] = time();  // update creation time
      }

      if (isset($_SESSION['userId'])) {
        $db = dbConnect();
        $result = $db->query(
          "SELECT id, username, email_address, password_hash, role, timezone ".
          "FROM pogoco_user ".
          "WHERE id = '".$_SESSION['userId']."'");
        if ($result->num_rows === 1) {
          $this->userRow = $result->fetch_assoc();
          $this->permissions = $this->SESSION_PERMISSIONS;
        }
        $db->close();
      }
    }
  }

  public function login($username, $password) {

    $errors = array();

    $db = dbConnect();

    $result = $db->query(
      "SELECT id, username, password_hash, email_address, role ".
      "FROM pogoco_user ".
      "WHERE username = '".$username."'");

    if ($result->num_rows === 1) {
      $tempUserRow = $result->fetch_assoc();

      if (empty($tempUserRow['password_hash']) ||
          !password_verify($password, $tempUserRow['password_hash'])) {
        array_push($errors, buildError("Invalid password", "password"));
      } else {
        $this->userRow = $tempUserRow;
        $_SESSION['userId'] = $this->userRow['id'];
        $this->permissions = $this->SESSION_PERMISSIONS;
      }
    } else {
      array_push($errors, buildError("Invalid user", "username"));
    }

    $db->close();

    return $errors;
  }

  public function logout() {
    session_unset();
    session_destroy();
    $permissions = $NO_PERMISSIONS;
  }

  public function register($username, $password, $email) {

    $errors = array();

    if ($this->isLoggedIn()) {
      array_push($errors, buildError("Already logged in"));
      return $errors;
    }

    $db = dbConnect();

    // No username clash
    $sql = "SELECT id FROM pogoco_user WHERE username = '".$username."'";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
      array_push($errors, buildError("Username already taken", "username"));
    }

    // No email clash
    $sql = "SELECT id FROM pogoco_user WHERE email_address = '".$email."'";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
      array_push($errors, buildError("Email address already in use", "email_address"));
    }

    if (sizeof($errors) > 0) {
      return $errors;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $fields = array("id", "username", "password_hash", "email_address");
    $values = array("UUID()", "'".$username."'", "'".$password_hash."'", "'".$email."'");

    $sql = "INSERT INTO pogoco_user (" . implode(",", $fields) . ") VALUES (" . implode(",", $values) . ")";
    $result = $db->query($sql);

    $sqlError = mysqli_error($db);
    if ($sqlError !== "") {
      array_push($errors, buildError($sqlError));
    }

    return $errors;
  }

  public function changePassword($current_password, $new_password, $repeat_password) {

    $errors = array();

    if (!$this->isLoggedIn()) {
      array_push($errors, buildError("Not logged in"));
      return $errors;
    }

    if (!password_verify($current_password, $this->userRow['password_hash'])) {
      array_push($errors, buildError("Incorrect current password", "current_password"));
    } else {

      if (empty($new_password)) {
        array_push($errors, buildError("Password cannot be blank", "new_password"));
        return $errors;
      }

      if ($new_password !== $repeat_password) {
        array_push($errors, buildError("Passwords don't match", "repeat_password"));
        return $errors;
      }

      $db = dbConnect();

      $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);

      $sql = "UPDATE pogoco_user SET password_hash = '".$passwordHash."' WHERE id = '".$this->getUserId()."'";
      $result = $db->query($sql);

      $sqlError = mysqli_error($db);
      if ($sqlError !== "") {
        array_push($errors, buildError($sqlError));
      }

      $db->close();
    }

    return $errors;
  }

  public function generateApiKey(&$newKey) {

    $errors = array();

    if (!$this->isLoggedIn()) {
      array_push($errors, buildError("Not logged in"));
      return $errors;
    }

    $db = dbConnect();

    $sql = "DELETE FROM pogoco_user_api WHERE user = '".$this->getUserId()."'";
    $result = $db->query($sql);

    $sqlError = mysqli_error($db);
    if ($sqlError !== "") {
      array_push($errors, buildError("Couldn't delete existing API key: ", $sqlError));
    } else {

      $newKey = $this->generateRandomString(50);
      $keyHash = hash('sha256', $newKey);

      $sql = "INSERT INTO pogoco_user_api VALUES ('$keyHash','".$this->getUserId()."')";
      $result = $db->query($sql);

      $sqlError = mysqli_error($db);
      if ($sqlError !== "") {
        array_push($errors, buildError($sqlError));
      }
    }

    $db->close();

    return $errors;
  }

  public function retrieveUsername($email_address) {

    global $site_title;

    $errors = array();

    if ($this->isLoggedIn()) {
      array_push($errors, buildError("Already logged in"));
      return $errors;
    }

    $db = dbConnect();

    $sql = "SELECT username FROM pogoco_user WHERE email_address = '".$email_address."'";
    $result = $db->query($sql);
    if ($result->num_rows === 1) {
      $row = $result->fetch_assoc();
      sendmailbymailgun(
        $email_address,
        $row['username'],
        $site_title . " username",
        "<p>Your username for wopogo.uk is '".$row['username']."'</p>"
      );
      /*mail(
        $email_address,
        $site_title . " username",
        "Hi,\n\nYour username for wopogo.uk is '".$row['username']."'",
        "From: no-reply@wopogo.uk"
      );*/
    } else {
      array_push($errors, buildError("Unknown email address", "email_address"));
    }

    $db->close();

    return $errors;
  }

  public function resetPassword($username) {

    global $site_title;

    $errors = array();

    if ($this->isLoggedIn()) {
      array_push($errors, buildError("Already logged in"));
      return $errors;
    }

    $db = dbConnect();

    $sql = "SELECT id, email_address FROM pogoco_user WHERE username = '".$username."'";
    $result = $db->query($sql);
    if ($result->num_rows === 1) {
      $row = $result->fetch_assoc();
      if (!empty($row['email_address'])) {

        $newPassword = $this->generateRandomString(10);
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE pogoco_user SET password_hash = '".$passwordHash."' WHERE id = '". $row['id']."'";
        $result = $db->query($sql);

        $sqlError = mysqli_error($db);
        if ($sqlError !== "") {
          array_push($errors, buildError($sqlError));
        } else {
          sendmailbymailgun(
            $row['email_address'],
            $username,
            $site_title . " password reset",
            "<p>Your password has been reset to '".$newPassword."'</p>"
          );
           /*mail(
            $row['email_address'],
            $site_title . " password reset",
            "Hi $username,\n\nYour password has been reset to '".$newPassword."'",
            "From: no-reply@wopogo.uk"
          );*/
        }

      } else {
        array_push($errors, buildError("No email address for user"));
      }
    } else {
      array_push($errors, buildError("Unknown user", "username"));
    }

    $db->close();

    return $errors;
  }

  public function isLoggedIn() {
    return !is_null($this->userRow);
  }

  public function getUserId() {
    if (is_null($this->userRow)) {
      return NULL;
    } else {
      return $this->userRow['id'];
    }
  }

  public function getUsername() {
    if (is_null($this->userRow)) {
      return NULL;
    } else {
      return $this->userRow['username'];
    }
  }

  public function getTimezone() {
    if (is_null($this->userRow)) {
      return "Europe/London";
    } else {
      if (is_null($this->userRow['timezone']) ||
          empty($this->userRow['timezone'])) {
        return "Europe/London";
      } else {
        return $this->userRow['timezone'];
      }
    }
  }

  public function hasPermission($permission) {
    return array_key_exists($permission, $this->permissions);
  }

  private function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }
}

?>
