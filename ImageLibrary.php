<?php

class ImageLibrary {

  const temp_file = "temp.png";

  public static function resize($img, $maxWidth, $maxHeight) {

    print "resize of img (".strlen($img)." bytes)\n";

    $res= @imagecreatefromstring($img);
    if ($res === FALSE) return array(FALSE,FALSE,FALSE);

    $width = imagesx($res);
    $height = imagesy($res);

    if ($width==0 || $height==0) {
      print "Error parsing image to resize :(\n";
      return array(FALSE,FALSE,FALSE);
    }

    if ($width <= $maxWidth && $height <= $maxHeight) {
      return array($img, $width, $height);
    }

    $ratio = $width/$height;
    $maxRatio = $maxWidth/$maxHeight;
    if ($maxRatio > $ratio) {
      $new_width = $maxHeight*$ratio;
      $new_height = $maxHeight;
    } else {
      $new_width = $maxWidth;
      $new_height = $maxWidth/$ratio;
    }
    $new_res = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($new_res, $res, 0,0,0,0, $new_width, $new_height, $width, $height);
    imagedestroy($res);
    imagejpeg($new_res, ImageLibrary::temp_file);
    imagedestroy($new_res);
    $new_img = file_get_contents(ImageLibrary::temp_file);
    unlink(ImageLibrary::temp_file);

    return array($new_img, $new_width, $new_height);
  }

  public static function aggregate($list, $bgcolor, $spacer) {
    $width = 0;
    $height = 0;
    $images = array();
    $offsets = array();
    $hashes = array();
    foreach ($list as $item) {
      $hash = md5($item["thumbnail"]);
      if (isset($hashes[$hash])) {
        $res = $hashes[$hash];
      } else {
        $res = @imagecreatefromstring($item["thumbnail"]);
        if ($res===FALSE) { // not an image
          continue;
        }
        $hashes[$hash] = $item["id"];
        $width = max($width, imagesx($res));
        $height += $spacer + imagesy($res);
      }
      $images[$item["id"]] = $res;
    }
    if ($width==0) {
      return array(FALSE,FALSE);
    }
    // ok. create our transparent canvas
    $res = imagecreatetruecolor($width, $height);
    $trans_color = imagecolorallocate( $res,
      hexdec($bgcolor[0].$bgcolor[1]),
      hexdec($bgcolor[2].$bgcolor[3]),
      hexdec($bgcolor[4].$bgcolor[5]) );
    imagefilledrectangle( $res, 0,0, $width, $height, $trans_color);

    $y=0;
    foreach ($images as $id=>$image) {
      if (getType($image)=="string") {
        // it's a duplicate thumbnail. just point the offset to it and be done.
        $offsets[$id] = $offsets[$image];
      } else {
        $w = imagesx($image);
        $h = imagesy($image);
        $x=$width-$w;
        $offsets[$id] = array($x,$y,$w,$h);
        $y += $spacer;
        imagecopy($res, $image, $x,$y, 0,0,  $w,$h);
        imagedestroy($image);
        $y+=$h;
      }
    }
    imagejpeg($res, ImageLibrary::temp_file);
    imagedestroy($res);
    $image = file_get_contents(ImageLibrary::temp_file);
    unlink(ImageLibrary::temp_file);
    return array($image, $offsets);
  }

}
