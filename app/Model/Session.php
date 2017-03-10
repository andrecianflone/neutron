<?php

namespace Neutron\Model;

class Session {

  /**
   * Start the session
   */
  function __construct() {
    session_start();
  }

  /**
   * get the value for this session key
   * @param string $key
   * @return mixed
   */
  public function get($key) {
    if ($this->exists($key)) {
      return $_SESSION[$key];
    } else {
      return null;
    }
  }

  /**
   * set new sessions parameter
   * @param string $key
   * @param string $value
   */
  public function set($key, $value) {
    $_SESSION[$key] = $value;
  }

  /**
   * check if key exists in settings
   * @param string $key
   * @return bool
   */
  public function exists($key) {
    return isset($_SESSION[$key]);
  }

  /**
   * unset var
   */
  public function destroy() {
    session_destroy();
  }

}

?>
