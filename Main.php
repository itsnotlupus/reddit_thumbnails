#!/usr/bin/php
<?php

define( "COOKIES", trim(file_get_contents("cookies")));
define( "SUB",  "cfabbro");
define( "MAX_WIDTH", 100);
define( "MAX_HEIGHT", 100);
define( "BUCKETS", 25);
define( "BUCKET_SIZE", 490000); // a bit under 500K for overhead and such

define( "BGCOLOR", "feefee");
define( "MARGIN", 15); // vertical pixels to keep transparent between bitmaps.

function __autoload($class) {
  require_once "$class.php";
}

$reddit = new Reddit(SUB, COOKIES);
$resolver = new URLResolver("images");
$thumbnailer = new Thumbnailer(MAX_WIDTH, MAX_HEIGHT, "thumbnails", $resolver);
$scanner = new Scanner($reddit, $thumbnailer);
$shuffler = new Shuffler(BUCKETS, BUCKET_SIZE, BGCOLOR, MARGIN);
$updater = new Updater($reddit);

$scanned  = $scanner->scan(); // take a snapshot of reddit and of its thumbnails

$shuffled = $shuffler->shuffle($scanned); // compose thumbnails into images

$updater->update($shuffled); // post stuff back to the reddit servers

