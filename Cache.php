<?php

abstract class Cache {

  var $dir;

  public function __construct($dir) {
    $this->dir = $dir;
    if (!file_exists($dir)) {
      mkdir($dir);
    }
  }

  public function get($id) {
    // XXX this should include an incremental cleanup mechanism
    $hash = md5($id);
    $d1 = $this->dir."/".substr($hash,0,2);
    $d2 = $d1."/".substr($hash,2,2);
    $file = $d2."/".$hash;
    if (file_exists($file)) {
      print get_class($this).": returning hit on $id\n";
      return file_get_contents($file);
    }
    $data = $this->acquire($id);
    if ($data===FALSE) return FALSE;
    if (!file_exists($d1)) {
      print "Attempting to mkdir $d1\n";
      mkdir($d1);
    }
    if (!file_exists($d2)) {
      mkdir($d2);
    }
    file_put_contents($file, $data);
    return $data;
  }

  abstract protected function acquire($id);

}
