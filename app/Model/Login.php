<?php

namespace Neutron\Model;

class Login {
  /**
   * @var PDO $db passed to constructor
   * @var Session $session passed to constructor
   */
  private $db;
  private $session;

  /**
   * @param PDO $db database from app object
   * @param Session $session
   */
  function __construct($db, $session) {
    $this->db = $db;
    $this->session = $session;
  }

  /**
   * Make sure user logged in
   */
  public function isAuthed() {
    $logged = $this->session->exists('admin_id');
    return $logged;
  }

  /**
   * get user info
   * @param string $username
   * @return mixed
   */
  private function find_admin_by_username($username) {
    $sql  = "SELECT * ";
    $sql .= "FROM users ";
    $sql .= "WHERE username = :username ";
    $sql .= "LIMIT 1";

    $query = $this->db->prepare($sql);
    $params = array(':username' => $username);
    $query->execute($params);

    if($query->rowCount() > 0) {
      return $query->fetchAll()[0]; // return first class
    } else {
      return null;
    }
  }

  public function attempt_login($username, $password) {
    // get username's hashed pass from db
    $admin = $this->find_admin_by_username($username);
    if ($admin) {
      // found admin, now check password
      if ($this->check_password($password, $admin->hashed_password)) {
        // password matches
        $this->session->set('admin_id', $username);
        return $admin;
      } else {
        // password does not match
        return null;
      }
    } else {
      // admin not found
      return null;
    }
  }

  public function logout() {
    $this->session->destroy();
  }

  //===========================================================================
  // PASSWORD
  //===========================================================================

  public function generate_hash($password) {
    $hash = password_hash($password, PASSWORD_BCRYPT, array("cost" => 10));
    //Make sure the hash worked
    $match = $this->check_password($password, $hash);
    if ($match) {
      return $hash;
    } else {
      return false;
    }
  }

  private function check_password($password, $hash) {
    if (password_verify($password, $hash)) {
        return true;
    } else {
        return false;
    }
  }

//old
  private function password_encrypt($password) {
    $hash_format = "$2y$10$"; // Tells PHP to use Blowfish with a "cost" of 10
    $salt_length = 22; // Blowfish salts should be 22-characters or more
    $salt = generate_salt($salt_length);
    $format_and_salt = $hash_format . $salt;
    $hash = crypt($password, $format_and_salt);
    return $hash;
  }

  private function generate_salt($length) {
    // Not 100% unique, not 100% random, but good enough for a salt
    // MD5 returns 32 characters
    $unique_random_string = md5(uniqid(mt_rand(), true));
    // Valid characters for a salt are [a-zA-Z0-9./]
    $base64_string = base64_encode($unique_random_string);
    // But not '+' which is valid in base64 encoding
    $modified_base64_string = str_replace('+', '.', $base64_string);
    // Truncate string to the correct length
    $salt = substr($modified_base64_string, 0, $length);

    return $salt;
  }

  private function password_check($password, $existing_hash) {
    // existing hash contains format and salt at start
    $hash = crypt($password, $existing_hash);
    if ($hash === $existing_hash) {
      return true;
    } else {
      return false;
    }
  }


}
?>
