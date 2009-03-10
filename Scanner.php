<?php

class Scanner {

  var $reddit;
  var $thumbnailer;

  var $data;

  function __construct($reddit, $tb) {
    $this->reddit = $reddit;
    $this->thumbnailer = $tb;
  }

  public function scan() {
    $changed = false;
    $stories = $this->reddit->getStories();
    $storyCount = 0;
    $out = array();
    foreach ($stories as $story) {
      if ($story->kind != "t3") continue;
      $data = $story->data;
      $id = $data->id;

      $comments = $this->reddit->getComments($id);
      $thumbs = array();

      $this->scanComments($thumbs, $comments);

      // sort comments so upvoted comments have priority for thumbnails
      uasort($thumbs, array($this, 'commentCmp'));

      if (count($thumbs)>0) {
        $out[$id] = $thumbs;
      }
      $storyCount++;
    }
    // return something useful..
    $this->data = $out;
    return $out;
  }

  function commentCmp($a, $b) {
    $a = $a["score"];
    $b = $b["score"];
    return ($a == $b)?0:($a < $b)?1:-1;
  }

  protected function scanComments(&$thumbs, $comments) {
    foreach ($comments as $comment) {
      $this->scanComment($thumbs, $comment->data);
    }
  }

  protected function scanComment(&$thumbnails, $data) { 
    $body = $data->body;
    $cid = $data->id;
    $created = $data->created;
    //extract URLs with some silly regexp..
    preg_match_all("{(http://[^)\] \r\t\n]+\.(png|gif|jpg|jpeg)(\?[^)\] \r\t\n]*)?)}", $body, $out);
    $urls = $out[1];
    if (count($urls)>0) {
      $url = $urls[0];
      // get a thumbnail here.
      $thumbnail = $this->thumbnailer->get($url);
      $thumbnails[$cid] = array(
        "id"=>$cid, 
        "url"=>$url,  
        "thumbnail"=>$thumbnail, 
        "sid"=>substr($data->link_id,3),
        "score"=>((int)$data->ups)-((int)$data->downs));
    }
    // check for children
    if ($data->replies) {
      $this->scanComments($thumbnails, $data->replies->data->children);
    }
  }

  public function getData() {
    return $this->data;
  }

}
