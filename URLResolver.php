<?php

class URLResolver extends Cache {

  protected function acquire($url) {
    return Network::fetch($url);
  }
}
