<?php
/**
 * BCScraper
 *
 * Scrapes Bandcamp page for metadata.
 *
 * This file is part of Music Card plugin
 */

namespace Grav\Plugin\MusicCard;

class BCScraper
{    
    
    /**
     * Scrape metadata from Bandcamp
     *
     * @param  string $url URL of track or album
     *
     * @return array       Metadata object
     */
    public function scrape($url)
    {
        // Test URL
        if (preg_match("/http[s]?:\/\/.*\.bandcamp\.com\/(.+)\/.*/", $url, $type)) {
            // Get Type (album or track)
            $type = $type[1];
            // Get HTML
            $html = file_get_contents($url);
            // Get JSON containing all the data we need-THANKS BANDCAMP!! :)
            $album = $this->getJsonData($html);
            $tracks = $album->track->itemListElement;

            $albumTitle = $album->name;
            $artist = $album->byArtist->name;
            $cover= $album->image;
            $releaseDate = $album->datePublished; //TODO: make php date
            $creditText = $album->creditText;
            $featuredTrackNum = $album->additionalProperty[1]->value;
            $trackCount = $album->numTracks;
            
            $covers = array();
            $featuredTrack = array();

            // Create urls for sm m lg covers.
            if (!is_null($cover)) {
                $covers["small"] = str_replace('_10', '_7', $cover);
                $covers["medium"] = str_replace('_10', '_2', $cover);
                $covers["large"] = $cover;
                $cover = $covers["small"];
            }

            // Create featured track
            if (!is_null($featuredTrackNum)) {
                    $ftrak = $tracks[intval($featuredTrackNum) - 1]->item; // zero-index offset
                    $featuredTrack["name"] = $ftrak->name;
                    $featuredTrack["url"] = $ftrak->mainEntityOfPage;
                    $featuredTrack["duration"] = $ftrak->duration;
            }
           
            // Build Metadata object
            $metadata = array(
                "type" => $type,
                "url" => $url,
                "artist" => $artist,
                "trackTitle" => $featuredTrack["name"],
                "albumTitle" => $albumTitle,
                "cover" => $cover,
                "covers" => $covers,
                "releaseDate" => $releaseDate,
                "tracks" => $this->getTracksMetadata($tracks),
                "trackCount" => $trackCount,
                "creditText" => $creditText,
                "featuredTrack" => $featuredTrack
            );

            return $metadata;
        } else {
            echo "This is not a proper Bandcamp track or album URL";
        }
    }

    private function getTracksMetadata($items)
    {
        $tracksMetadata = array();

        foreach($items as $item) {
            $position = $item->position;
            $track = $item->item;
            array_push($tracksMetadata,
                array(
                    "track" => $position,
                    "name" => $track->name,
                    "duration" => $track->duration
                ));
        }

        return $tracksMetadata;
    }
    
    private function getJsonData($html)
    {
        $startPhrase='<script type="application/ld+json">';
        $indexStart  =  strpos($html, $startPhrase)  + strlen($startPhrase);
        $indexFinish = strpos($html, '</script>', $indexStart);
        $jsonData = substr($html, $indexStart, ($indexFinish - $indexStart));
        return json_decode(trim($jsonData));
    }
}