#!/usr/bin/php
<?php

define( "COOKIES", trim(file_get_contents("cookies")));
define( "SUB",  "reddithax");
define( "MAX_WIDTH", 180);
define( "MAX_HEIGHT", 180);
define( "BUCKETS", 25);
define( "BUCKET_SIZE", 490000); // a bit under 500K for overhead and such

define( "FORCED_REFRESH_INTERVAL", 600); // how often to check a story if its comment count hasn't changed

define( "BGCOLOR", "ffffff"); // "dcd4b5"); //"feefee");
define( "MARGIN", 0); // 12); // vertical pixels to keep transparent between bitmaps.
define( "NEGMARGIN", 12); // if the MARGIN is 0, you probably want this to be 12.

function __autoload($class) {
  require_once "$class.php";
}

$time_start = time();
echo "# reddit_thumbnails::".SUB." started at ".date(DATE_RFC822,$time_start)."\n";

$reddit = new Reddit(SUB, COOKIES);
$resolver = new URLResolver("images");
$thumbnailer = new Thumbnailer(MAX_WIDTH, MAX_HEIGHT, "thumbnails", $resolver);
$scanner = new Scanner($reddit, $thumbnailer, FORCED_REFRESH_INTERVAL);
$shuffler = new Shuffler(BUCKETS, BUCKET_SIZE, BGCOLOR, MARGIN);
$updater = new Updater($reddit, NEGMARGIN);

$scanned  = $scanner->scan(); // take a snapshot of reddit and of its thumbnails

$shuffled = $shuffler->shuffle($scanned); // compose thumbnails into images

$updater->update($shuffled); // post stuff back to the reddit servers

echo "# reddit_thumbnails::".SUB." done in ".(time()-$time_start)." seconds.\n";
