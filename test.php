<?php
namespace Grav\Plugin\MusicCard;
include 'classes/BCScraper.php';

$scraper = new BCScraper();

$results = $scraper->scrape("https://slugmilk.bandcamp.com/album/volume-xx-island-girls");

print_r($results);