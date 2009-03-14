<?php

/* The shuffler organizes individual thumbnails
   into aggregated images
 */

class Shuffler {

  var $count; // how many images do we have to work with.
  var $max_size;
  var $buckets;

  var $spacer; // how much vertical space to keep between each thumbnail
  var $bgcolor;

  const FUDGE = 0.9; // we can't guarantee perfect packing. this "accounts" for that.

  public function __construct($bucket_count, $capacity, $bgcolor, $spacer) {
    $this->count = $bucket_count;
    $this->max_size = $capacity;
    $this->bgcolor = $bgcolor;
    $this->spacer = $spacer;
    // prepare our buckets.
    $this->buckets = array();
    for ($i=0;$i<$this->count;$i++) {
      $this->buckets[$i] = array();
    }
  }

  public function shuffle($data) {
    // much simpler approach:
    // each story gets one bucket. period.
    // comments are mostly sorted by worthyness.
    // comments that are too low may not get a thumbnail, that's fine.

    while (count($data)>$this->count) {
      array_pop($data);
    }

    $i=0;
    foreach ($data as $id=>$story) {
      $load = 0;
      foreach ($story as $comment) {
        $size = strlen($comment["thumbnail"]);
        $load += $size;
        if ($load > $this->max_size) break;
        $this->buckets[$i][] = $comment;
      }
      // ok, now we have all the top rated comments that can fit. great.
      // only problem: Their order will keep changing whenver comments get modded up or down
      // that would generate a lot of unnecessary uploads, so we order them better
      usort($this->buckets[$i], array($this, 'commentCmp'));
      $i++;
    }


    return $this->generate($i);
  }

  function commentCmp($a, $b) {
    // sort on something very static: the comment id.
    return strcmp($a["id"], $b["id"]);
  }

  protected function generate($used) {
    // time to create the aggregated image for each bucket.
    $out = array();
    for ($i=0;$i<$used;$i++) {
      list($image, $offsets) = ImageLibrary::aggregate($this->buckets[$i], $this->bgcolor, $this->spacer);
      if ($image !== FALSE) {
        print "Generated image for bucket #$i: ".strlen($image)." bytes\n";
        $out[] = array("image"=>$image, "offsets"=>$offsets, "id"=>$this->buckets[$i][0]["sid"]);
      }
    }

    return $out;

  }

}
