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
      $i++;
    }
    return $this->generate($i);
  }

  public function shuffle2($data) {
    // ok. magic heuristics go here.
    
    // - estimate the thumbnail weight of each story, then sort down with it
    $weights = array();
    $total_weight = 0;
    foreach ($data as $id=>$story) {
      $weight = 0;
      foreach ($story as $comment) {
        $weight += strlen($comment["thumbnail"]);
      }
      $weights[$id] = $weight;
      print "Weight for story $id = $weight\n";
      $total_weight += $weight;
    }
    // sanity check
    if ($total_weight > $this->count * $this->max_size * Shuffler::FUDGE ) {
      print "TOO MANY IMAGE XXX: we're not going to be able to fit everything\n";
      // XXX this is where I wish I had a comment creation-based logic to cut old stuff out.
      // XXX remove the %d oldest comments from the pile and recompute until we pass this check
    }
    // sort $weights
    arsort($weights);
    // just start shoving crap in the buckets, starting with the heavier stuff
    $current = -1;
    $spillage = 0; // if we spill, that means we'll have to pack things up tighter later on.
    foreach ($weights as $id=>$weight) {
      if ($weight>$this->max_size) {
        // bucket overflow, we're going to spill into the next one. or next few.
        $current++;  // take a brand new bucket
        $load = 0; // bucket is empty
        foreach ($data[$id] as $comment) {
          $size = strlen($comment["thumbnail"]);
          if ($load>0 && $load + $size > $this->max_size) {
            // spillage!
            $spillage++;
            $current++;
            $load = 0;
          }
          // shove
          $this->buckets[$current][] = $comment;
          $load += $size;
        }
      } else {
        // ok, we can fit in a whole bucket.
        // but could we fit in a partially filled bucket too?
        if ($spillage>0 && $weight+$load < $this->max_size) {
          // sweet. that makes up for one spill.
          $spillage --;
        } else {
          // we couldn't, or we just don't need to.
          $current++;
          $load = 0;
        }
        foreach ($data[$id] as $comment) {
          $size = strlen($comment["thumbnail"]);
          $this->buckets[$current][] = $comment;
          $load += $size;
        }
      }
    }
    // yay. all shoved up.
    $used = $current+1;
    if ($used>$this->count) {
      // XXX dumb. this will fail earlier this $this->buckets[$current] will be undefined.
      print "ERROR XXX: Bucket Overflow (We had ".$this->count." buckets, but we filled ".($current+1)." buckets. Overflowing buckets will be ignored.\n";
      $used = $this->count;
    }
    return $this->generate($used);
  }

  protected function generate($used) {
    // time to create the aggregated image for each bucket.
    $out = array();
    for ($i=0;$i<$used;$i++) {
      list($image, $offsets) = ImageLibrary::aggregate($this->buckets[$i], $this->bgcolor, $this->spacer);
      print "Generated image for bucket #$i: ".strlen($image)." bytes\n";
      $out[] = array("image"=>$image, "offsets"=>$offsets, "id"=>$this->buckets[$i][0]["sid"]);
    }

    return $out;

  }

}
