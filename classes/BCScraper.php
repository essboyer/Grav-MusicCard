<?php
/**
 * BCScraper
 *
 * Scrapes Bandcamp page for metadata.
 * 
 * works in Q2 of 2023
 *
 * This file is part of Music Card plugin
 */

namespace Grav\Plugin\MusicCard;

class BCScraper
{    

    public function scrape($url)
    {
        // Test URL
        if (preg_match("/http[s]?:\/\/.*\.bandcamp\.com\/(.+)\/.*/", $url, $type)) {
            // Get Type (album or track)
            $type = $type[1];
            // Get HTML
            $html = file_get_contents($url);
            // Get JSON containing all the data we need-THANKS BANDCAMP!! :)
            // NOTE: structure differs between tracks and albums
            $album = $this->getJsonData($html);

            $artist = $album->byArtist->name;
            $cover= $album->image;
            $releaseDate = $album->datePublished; //TODO: make php date
            $featuredTrackNum = $album->additionalProperty[1]->value;
            $trackCount = $album->numTracks ?? 1;
            
            $covers = array();
            $tracks = array();
            $featuredTrack = array();
            $trackTitle = '';
            $creditText = '';

            // Create urls for sm m lg covers.
            if (!is_null($cover)) {
                $covers = array(
                    "small" => str_replace('_10', '_7', $cover),
                    "medium" => str_replace('_10', '_2', $cover),
                    "large" => $cover
                );
            }


            // Album Specifics
            if($type == 'album' ) {
                $albumTracks = $album->track->itemListElement;
                $albumTitle = $album->name;
                $creditText = $album->creditText;

                // Create tracks list
                if (!is_null($albumTracks)) {
                    $tracks = $this->getTracksMetadata($albumTracks);
                }

                // Create featured track
                if (!is_null($featuredTrackNum)) {
                        $ftrak = $albumTracks[intval($featuredTrackNum) - 1]->item; // zero-index offset
                        $featuredTrack = array(
                            "name" => $ftrak->name,
                            "url" => $ftrak->mainEntityOfPage,
                            "duration" => $ftrak->duration
                        );
                }

            } elseif ($type == 'track') {
                $albumTitle = $album->inAlbum->name;
                $trackTitle = $album->name;
            } 
           
            // Build Metadata object
            $metadata = array(
                "type" => $type,
                "url" => $url,
                "artist" => $artist,
                "trackTitle" => $trackTitle,
                "albumTitle" => $albumTitle,
                "cover" => $cover,
                "covers" => $covers,
                "releaseDate" => $releaseDate,
                "tracks" => $tracks,
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
                    "duration" => $track->duration,
                    "url" => $track->mainEntityOfPage
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