<?php

class URLResolver extends Cache {

  protected function acquire($url) {
    // if the fetch fails, return an empty string rather than FALSE
    // this will prevent refetching attempt later.
    $data = Network::fetch($url);
    if ($data === FALSE) { return ""; }
    return $data;
  }
}
