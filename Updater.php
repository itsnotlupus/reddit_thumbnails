<?php

class Updater extends StateKeeper {

  var $reddit;
  var $negmargin;

  public function __construct($reddit, $negmargin) {
    parent::__construct(array("css"=>"", "pics"=>array()));
    $this->reddit = $reddit;
    $this->negmargin = $negmargin;
  }

  public function update($data) {
    $css = "";

    // tweak the CSS, as we assume a specific font-size in .tagline elements
    $css .= "\n.tagline{font-size:12px;line-height:10px;height:12px;}\n";
    // trace a nice inheritance path from #siteTable_* to each actual comment
    $css .= ".comment,.entry,.noncollapsed,.child,.sitetable{background-image:inherit;background-repeat:no-repeat;background-position:-1024px 0px;}\n";

    // load the local state, which is expected to mirror the server's state
    $state = $this->state;

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
      // cfabbro's genius idea: use the URL only once and inherit the crap out of it
      // this works around reddit's propensity to make every URL unique and ruin caching
      $css .= "#siteTable_t3_$sid{ background:url(%%$sid%%) no-repeat -1024px 0px;}\n";
      foreach ($item["offsets"] as $id=>$offset) {
	list($x,$y,$w,$h) = $offset;
        $h = max(0,$h-$this->negmargin);
        $css .= "div.id-t1_$id > div > div > .tagline { background-image:inherit; background-repeat:no-repeat;background-position: -".$x."px -".$y."px; padding-bottom: ".$h."px; margin-bottom: -".$h."px; padding-left: ".($w+10)."px;}\n";
	$css .= "div.id-t1_$id > div > .noncollapsed { min-height: ".($h+15)."px; }\n";
        $css .= "div.id-t1_$id > div > div > .commentbody { margin-left: ".($w+10)."px; }\n";
        $css .= "div.id-t1_$id > div > div > .flat-list { margin-left: ".($w+10)."px; }\n";
      }
    }
    $css = "/*--BEGIN THUMBNAILS CSS--*/\n".$css."/*--END THUMBNAILS CSS--*/\n";

    // post CSS
    if ($css != $state["css"]) {

      $oldCss = $this->reddit->getStylesheet();
      print "oldCss = ".strlen($oldCss)." bytes.\n";
      if ($oldCss == "") {
        $oldCss = file_get_contents("custom.css");
      }
      // inject new CSS into old
      $newCss = preg_replace('{/\*--BEGIN THUMBNAILS CSS--\*/.*/\*--END THUMBNAILS CSS--\*/}s', $css, $oldCss);
      if ($newCss == $oldCss) {
        $newCss = $oldCss . "\n" . $css;
      }
      if($this->reddit->postStylesheet($newCss)) {
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
    $this->state = $state;
  }

}
 
