<?php

// The Thumbnailer makes thumbnails, rather than nails thumbs.

class Thumbnailer extends Cache {

  var $width;
  var $height;
  var $resolver;

  public function __construct($width, $height, $dir, $resolver) {
    parent::__construct($dir);

    $this->width = $width;
    $this->height = $height;
    $this->resolver = $resolver;

  }

  protected function acquire($url) {
    $data = $this->resolver->get($url);
    if ($data === FALSE) return FALSE;

    list($thumbnail,$width,$height) = ImageLibrary::resize($data, $this->width, $this->height);
    if ($thumbnail === FALSE) return FALSE;

    return $thumbnail;

  }
}
