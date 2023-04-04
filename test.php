<?php
namespace Grav\Plugin\MusicCard;
include 'classes/BCScraper.php';

$scraper = new BCScraper();

$results = $scraper->scrape("https://slugmilk.bandcamp.com/track/matcho-2");

print_r($results);