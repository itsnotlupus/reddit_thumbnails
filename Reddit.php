<?php

class Reddit extends StateKeeper {
  
  var $sub;
  var $cookies;
  var $uh;
  var $bucket;
  const temp_file = "temp.png";

  const param_file = "reddit.params";
  const PARAM_REFRESH = 1800; // 30 minutes

  function __construct($sub, $cookies) {
    parent::__construct(FALSE);
    $this->sub = $sub;
    $this->cookies= $cookies;
    $this->getParams();
  }

  protected function getReddit($url, $tries=3) {
    $json = Network::fetch("http://www.reddit.com".$url, $this->cookies);
    $obj = json_decode($json);
    $t = getType($obj);
    if ($tries>0 && $t!="object" && $t!="array") {
      echo "ERROR: getReddit($url) failed. Retrying...\n";
      echo "response was: \n$json\n";
      return $this->getReddit($url, $tries-1);
    }
    return $obj;
  }
  
  protected function getParams() {
    // get a locally cached version first
    $redditParams = $this->state;
    if ($redditParams!==FALSE) {
      $this->uh = $redditParams["uh"];
      $this->bucket = $redditParams["bucket"];
      $ts = $redditParams["ts"];
      if (time()-$redditParams["ts"]<Reddit::PARAM_REFRESH) {
        print "Reddit::getParams(): Reusing locally cached uh and bucket.\n";
        return;
      }
    }

    $html = $this->getReddit("/r/".$this->sub."./about/stylesheet", 0);
    // we only need two chunks of data from there.
    if (preg_match("{ modhash: '([^']+)',}", $html, $out)) {
      $this->uh = $out[1];
      $ts = time();
    }
    if (preg_match("{ reddit.cur_site = \"([^\"]+)\";}", $html, $out)) {
      $this->bucket = $out[1];
      $ts = time();
    }
    $this->state = array(
	"ts"=>$ts,
	"uh"=>$this->uh,
	"bucket"=>$this->bucket
    );
  }

  public function getStylesheet() {
    $html = $this->getReddit("/r/".$this->sub."/about/stylesheet", 0);
    if (preg_match('{<textarea rows="20" cols="20" id="stylesheet_contents" name="stylesheet_contents" >(.*?)</textarea>}s', $html, $out)) {
      $css = html_entity_decode($out[1]);
      return $css;
    }
    return "";
  }
  
  public function postStylesheet($css, $tries=3) {
 
    $post = array(
      "default_stylesheet" => "", // no need to send 36K for nothing
      "id" => "#subreddit_stylesheet",
      "op" => "save",
      "r" => $this->sub,
      "stylesheet_contents" => $css,
      "thumbbucket" => $this->bucket,
      "uh" => $this->uh
    );
  
    $ret = Network::post("http://www.reddit.com/api/subreddit_stylesheet", $this->cookies, $post);
    if (strpos($ret, "background")===FALSE) {
      print "ERROR: CSS post failed: $ret\n";
      if ($tries>0) {
        return $this->postStylesheet($css, $tries-1);
      } else {
        return FALSE;
      }
    }
    return TRUE;
  }
  
  public function uploadImage($id, $img, $tries=3) {
  
    $file = Reddit::temp_file;
    file_put_contents($file, $img);
  
    $post = array(
      "uh" => $this->uh,
      "r" => $this->sub,
      "file" => "@$file",
      "upload" => "",
      "name" => $id
    );
  
    $ret = Network::post("http://www.reddit.com/api/upload_sr_img", $this->cookies, $post);
    unlink($file);

    if (strpos($ret, ' [["BAD_CSS_NAME", ""], ["IMAGE_ERROR", ""]]')===FALSE) {
      print "ERROR: upload image: $ret\n";
      if ($tries>0) {
        return $this->uploadImage($id, $img, $tries-1);
      } else {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function deleteImage($id, $tries=3) {
    $post = array(
      "executed" => "deleted",
      "id" => "",
      "img_name" => $id,
      "r" => $this->sub,
      "uh" => $this->uh
    );
    $ret = Network::post("http://www.reddit.com/api/delete_sr_img", $this->cookies, $post);
    print "DELETE image: $ret\n";
    // XXX write a test to know when this fails and retry..
    return $ret;
  }

  public function getStories() {
    $r = $this->getReddit("/r/".$this->sub.".json");
    return $r->data->children;
  }

  public function getComments($id) {
    $s = $this->getReddit("/r/".$this->sub."/comments/".$id.".json");
    return $s[1]->data->children;
  }


}
