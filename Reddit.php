<?php

class Reddit {
  
  var $sub;
  var $cookies;
  var $uh;
  var $bucket;
  const temp_file = "temp.png";

  const param_file = "reddit.params";
  const PARAM_REFRESH = 1800; // 30 minutes

  function __construct($sub, $cookies) {
    $this->sub = $sub;
    $this->cookies= $cookies;
    $this->getParams();
  }

  protected function getReddit($url) {
    $json = Network::fetch("http://www.reddit.com".$url, $this->cookies);
    $obj = json_decode($json);
    return $obj;
  }
  
  protected function getParams() {
    // get a locally cached version first
    $redditParams = unserialize(file_get_contents(Reddit::param_file));
    if ($redditParams!==FALSE) {
      if (time()-$redditParams["ts"]<Reddit::PARAM_REFRESH) {
        print "Reddit::getParams(): Reusing locally cached uh and bucket.\n";
        $this->uh = $redditParams["uh"];
        $this->bucket = $redditParams["bucket"];
        return;
      }
    }

    $html = $this->getReddit("/r/".$this->sub."./about/stylesheet");
    // we only need two chunks of data from there.
    preg_match("{ modhash: '([^']+)',}", $html, $out);
    $this->uh = $out[1];
    preg_match("{ reddit.cur_site = \"([^\"]+)\";}", $html, $out);
    $this->bucket = $out[1];
    file_put_contents(Reddit::param_file, serialize(array(
	"ts"=>time(),
	"uh"=>$this->uh,
	"bucket"=>$this->bucket
    )));
  }
  
  public function postStylesheet($css) {
  
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
    if (strpos($ret, "commentbody")===FALSE) {
      print "ERROR: CSS post failed: $ret\n";
      return FALSE;
    }
    return TRUE;
  }
  
  public function uploadImage($id, $img) {
  
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
      return FALSE;
    }
    return TRUE;
  }

  public function deleteImage($id) {
    $post = array(
      "executed" => "deleted",
      "id" => "",
      "img_name" => $id,
      "r" => $this->sub,
      "uh" => $this->uh
    );
    $ret = Network::post("http://www.reddit.com/api/delete_sr_img", $this->cookies, $post);
    print "DELETE image: $ret\n";
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
