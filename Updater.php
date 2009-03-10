<?php

class Updater {

  public function __construct($reddit) {
    $this->reddit = $reddit;
  }

  public function update($data) {
    // generate some CSS
    $css = file_get_contents("custom.css");

    // load the local state, which is expected to mirror the server's state
    $state = @unserialize(file_get_contents("updater.state"));
    if ($state === FALSE) {
      $state = array("css"=>"", "pics"=>array());
    }

    // upload thumbnails
    $used = array();
    foreach ($data as $item) {
      $sid = $item["id"];
      $used[] = $sid;
      $hash = md5($item["image"]);
      if ($state["pics"][$sid]!=$hash) {
        if ($this->reddit->uploadImage($sid, $item["image"])) {
          $state["pics"][$sid]=$hash;
        }
      } else {
        print "Updater: thumbnails for $sid did not change.\n";
      }
      foreach ($item["offsets"] as $id=>$offset) {
        $css .= "div.id-t1_$id > div > div > .tagline { background:url(%%$sid%%) no-repeat 0px -".$offset[0]."px; padding-bottom: ".$offset[1]."px; margin-bottom: -".$offset[1]."px;}\n";
	$css .= "div.id-t1_$id > div > .noncollapsed { min-height: ".($offset[1]+15)."px; }\n";
        $css .= "div.id-t1_$id > div > div > .commentbody { margin-left: ".($offset[2]+10)."px; }\n";
        $css .= "div.id-t1_$id > div > div > .flat-list { margin-left: ".($offset[2]+10)."px; }\n";
      }
    }
    // post CSS
    if ($css != $state["css"]) {
      if($this->reddit->postStylesheet($css)) {
        $state["css"] = $css;
      }
    } else {
      print "Updater: CSS did not change.\n";
    }
    // delete unused thumbnails
    foreach ($state["pics"] as $id=>$hash) {
      if (!in_array($id, $used)) {
        // we don't use this one anymore.
	print "Deleting thumbnails for $id...\n";
        if ($this->reddit->deleteImage($id)) {
          unset($state["pics"][$id]);
        }
      }
    }

    // save local state
    file_put_contents("updater.state", serialize($state));
  }

}
 
