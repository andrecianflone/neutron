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

}
?>
