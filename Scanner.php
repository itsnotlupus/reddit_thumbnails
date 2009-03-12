<?php

class Scanner {

  var $reddit;
  var $thumbnailer;
  var $forceRefresh;

  function __construct($reddit, $tb, $forceRefresh = 300) {
    $this->reddit = $reddit;
    $this->thumbnailer = $tb;

    $this->forceRefresh = $forceRefresh;
  }

  public function scan() {
    // 1. get the subreddit main stories
    $stories = $this->reddit->getStories();
    if (getType($stories) != "array") {
      throw new Exception("Blaaargh");
    }
    // 2. get the cached version
    $cachedStories = @unserialize(file_get_contents("scanner.state"));
    if ($cachedStories === FALSE) {
      $cachedStories = array();
    }
    // 3. this is going to be our next cached state
    $newCachedStories = array();
    // 4. loop through the new feed
    $out = array();
    foreach ($stories as $story) {
      if ($story->kind != "t3") continue;
      $data = $story->data;
      $id = $data->id;
      // did we know about this story earlier?
      $refresh = false;
      if (isset($cachedStories[$id])) {
        $cs = $cachedStories[$id];
        // has the number of comments changed?
	if ($cs["num_comments"] == $data->num_comments) {
          // no? how long has it been since we checked?
          $delta = time() - $cs["refreshed"];
          if ($delta>$this->forceRefresh) {
            // it's been a while. refresh anyway.
            print "Refreshing $id because it's been a while.\n";
            $refresh = true;
          }
        } else {
          // yes. refresh to find out what changed
          print "Refreshing $id because num_comment has changed\n";
          $refresh = true;
        }
      } else {
        // new story? cool.
        print "Refreshing $id because it's a new story.\n";
        $refresh = true;
      }

      // update cache
      $newCachedStories[$id] = array(
        "num_comments" => $data->num_comments,
        "refreshed" => $refresh?time():$cs["refreshed"],
        "feed" => $cs["feed"]
      );

      // if necessary, fetch the comment feed
      if ($refresh) {
        $comments = $this->reddit->getComments($id);
        $thumbs = array();
        $this->scanComments($thumbs, $comments);
        // sort comments so upvoted comments have priority for thumbnails
        uasort($thumbs, array($this, 'commentCmp'));
        $newCachedStories[$id]["feed"] = $thumbs;
      } else {
        $thumbs = $cs["feed"];
      }

      if (count($thumbs)>0) {
        $out[$id] = $thumbs;
      }

    }
    // commit cache to disk
    file_put_contents("scanner.state", serialize($newCachedStories));
    // return something useful..
    return $out;
  }

  function commentCmp($a, $b) {
    $a = $a["score"];
    $b = $b["score"];
    return ($a == $b)?0:($a < $b)?1:-1;
  }

  protected function scanComments(&$thumbs, $comments) {
    if (getType($comments) != "array") {
      throw new Exception("comments is not an array. aborting");
    }
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
      //print "URL = $url, strlen(thumbnail)=".strlen($thumbnail)."\n";
    }
    // check for children
    if ($data->replies) {
      $this->scanComments($thumbnails, $data->replies->data->children);
    }
  }

}
