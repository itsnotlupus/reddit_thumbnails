<?php

class Updater {

  var $reddit;
  var $negmargin;

  public function __construct($reddit, $negmargin) {
    $this->reddit = $reddit;
    $this->negmargin = $negmargin;
  }

  public function update($data) {
    $css = file_get_contents("custom.css");
    // tweak the CSS, as we assume a specific font-size in .tagline elements
    $css .= "\n.tagline{font-size:12px;line-height:10px;height:12px;}\n";

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
    // generate some CSS
      foreach ($item["offsets"] as $id=>$offset) {
	list($x,$y,$w,$h) = $offset;
        $h = $h-$this->negmargin;
        $css .= "div.id-t1_$id > div > div > .tagline { background:url(%%$sid%%) no-repeat -".$x."px -".$y."px; padding-bottom: ".$h."px; margin-bottom: -".$h."px; padding-left: ".($w+10)."px;}\n";
	$css .= "div.id-t1_$id > div > .noncollapsed { min-height: ".($h+15)."px; }\n";
        $css .= "div.id-t1_$id > div > div > .commentbody { margin-left: ".($w+10)."px; }\n";
        $css .= "div.id-t1_$id > div > div > .flat-list { margin-left: ".($w+10)."px; }\n";
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
 
