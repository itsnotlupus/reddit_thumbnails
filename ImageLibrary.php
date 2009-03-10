<?php

class ImageLibrary {

  const temp_file = "temp.png";

  public static function resize($img, $maxWidth, $maxHeight) {

    print "resize of img (".strlen(img)." bytes)\n";

    $res= imagecreatefromstring($img);
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
    foreach ($list as $item) {
      $res = imagecreatefromstring($item["thumbnail"]);
      $width = max($width, imagesx($res));
      $height += $spacer + imagesy($res);
      $images[$item["id"]] = $res;
    }
    // ok. create our transparent canvas
    $res = imagecreatetruecolor($width, $height);
/*
    imagesavealpha($res, true);
    $trans_color = imagecolorallocatealpha($res, 0,0,0, 127);
*/
    $trans_index = imagecolorallocate( $res,
      hexdec($bgcolor[0].$bgcolor[1]),
      hexdec($bgcolor[2].$bgcolor[3]),
      hexdec($bgcolor[4].$bgcolor[5]) );
    $trans_color = imagecolortransparent( $res, $trans_index);
    imagefill( $res, 0,0, $trans_color);

    $y=0;
    foreach ($images as $id=>$image) {
      $w = imagesx($image);
      $h = imagesy($image);
      $offsets[$id] = array($y,$h,$w);
      $y += $spacer;
      imagecopy($res, $image, 0,$y, 0,0,  $w,$h);
      imagedestroy($image);
      $y+=$h;
    }
    imagejpeg($res, ImageLibrary::temp_file);
    imagedestroy($res);
    $image = file_get_contents(ImageLibrary::temp_file);
    unlink(ImageLibrary::temp_file);
    return array($image, $offsets);
  }

}
