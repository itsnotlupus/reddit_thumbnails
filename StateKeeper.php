<?php

class StateKeeper {

  protected $state;
  protected $statefile;

  const STATE_FOLDER = "state";

  public function __construct($default) {
    if (!file_exists(StateKeeper::STATE_FOLDER)) {
      mkdir(StateKeeper::STATE_FOLDER);
    }
    // attempt to load state
    $this->statefile = StateKeeper::STATE_FOLDER."/".strtolower(get_class($this)).".state";
    $this->state = @unserialize(file_get_contents($this->statefile));
    if ($this->state === FALSE) {
      $this->state = $default;
    }
  }

  public function __destruct() {
    file_put_contents($this->statefile, serialize($this->state));
  }
}

