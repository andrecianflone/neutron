<?php

namespace Neutron\Model;

/**
 * Simple class for Analytics
 */
class Analytics {
  private $enabled;
  private $script;

  function __construct($enabled, $script) {
    $this->enabled = $enabled;
    $this->script = $script;
  }

  public function getScript() {
    if ($this->enabled == "true") {
      return $this->script;
    } else {
      return "";
    }
  }

}
?>
