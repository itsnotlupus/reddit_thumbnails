<?php

class Network {

  public static function fetch($url, $cookies='') {
    print "GETting from $url\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($cookies!="") {
      curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // slow servers can get lost.
    $ret = curl_exec($ch);
    curl_close($ch);
    return $ret;
  }

  public static function post($url, $cookies, $body) {
    print "POSTing to $url\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
    curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6); // a little bit more patience with ol'man reddit
    $ret = curl_exec($ch);
    curl_close($ch);
    return $ret;
  }


}
