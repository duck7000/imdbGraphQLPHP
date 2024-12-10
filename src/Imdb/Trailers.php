<?php

#############################################################################
# imdbGraphQLPHP Trailers                https://www.imdb.com/trailers/     #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * Obtains information about trailers as seen on https://www.imdb.com/trailers/
 * https://www.imdb.com/trailers/
 * @Note thumbnail width and height are set in config, one setting for all methods!
 * @author Ed (github user: duck7000)
 */
class Trailers extends MdbBase
{

    protected $imageFunctions;
    protected $newImageWidth;
    protected $newImageHeight;

    /**
     * @param Config $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(Config $config = null, LoggerInterface $logger = null, CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->imageFunctions = new Image();
        $this->newImageWidth = $this->config->thumbnailWidth;
        $this->newImageHeight = $this->config->thumbnailHeight;
    }

    /**
     * Get the latest trailers as seen on IMDb https://www.imdb.com/trailers/
     * @return
     * Array
     *   (
     *      [0] => Array
     *          (
     *              [videoId] =>                    (string) (without vi)
     *              [titleId] =>                    (string) (without tt)
     *              [title] =>                      (string)
     *              [trailerCreateDate] =>          (string iso date) 2024-11-17T13:16:18.708Z
     *              [trailerRuntime] =>             (int) (in seconds!)
     *              [playbackUrl] =>                (string) This url will playback in browser only)
     *              [thumbnailUrl] =>               (string) (thumbnail (140x207) image of the title)
     *              [releaseDate] =>                (string) (date string: December 4, 2024)
     *              [contentType] =>                (string ) like Trailer Season 1 [OV]
     *          )
     *  )
     */
    public function recentVideo()
    {
        $recentVideoResults = array();
        $query = <<<EOF
query RecentVideo {
  recentVideos(
    limit: 100
    queryFilter: {contentTypes: TRAILER}
  ) {
    videos {
      id
      createdDate
      primaryTitle {
        id
        titleText {
          text
        }
        releaseDate {
          displayableProperty {
            value {
              plainText
            }
          }
        }
        primaryImage {
          url
          width
          height
        }
      }
      runtime {
        value
      }
      name {
        value
      }
    }
  } 
}
EOF;
        $data = $this->graphql->query($query, "RecentVideo");
        foreach ($data->recentVideos->videos as $edge) {
            $thumbUrl = null;
            $videoId = isset($edge->id) ?
                             str_replace('vi', '', $edge->id) : null;
            if (!empty($edge->primaryTitle->primaryImage->url)) {
                $fullImageWidth = $edge->primaryTitle->primaryImage->width;
                $fullImageHeight = $edge->primaryTitle->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->primaryTitle->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $thumbUrl = $img . $parameter;
            }
            $recentVideoResults[] = array(
                'videoId' => $videoId,
                'titleId' => isset($edge->primaryTitle->id) ?
                                   str_replace('tt', '', $edge->primaryTitle->id) : null,
                'title' => isset($edge->primaryTitle->titleText->text) ?
                                 $edge->primaryTitle->titleText->text : null,
                'trailerCreateDate' => isset($edge->createdDate) ?
                                             $edge->createdDate : null,
                'trailerRuntime' => isset($edge->runtime->value) ?
                                          $edge->runtime->value : null,
                'playbackUrl' => !empty($videoId) ?
                                        'https://www.imdb.com/video/vi' . $videoId . '/' : null,
                'thumbnailUrl' => $thumbUrl,
                'releaseDate' => isset($edge->primaryTitle->releaseDate->displayableProperty->value->plainText) ?
                                       $edge->primaryTitle->releaseDate->displayableProperty->value->plainText : null,
                'contentType' => isset($edge->name->value) ?
                                       $edge->name->value : null
            );
        }
        return $recentVideoResults;
    }
    
    /**
     * Get trending trailers as seen on IMDb https://www.imdb.com/trailers/
     * @return
     * Array
     *   (
     *      [0] => Array
     *          (
     *              [videoId] =>            (string) (without vi)
     *              [titleId] =>            (string) (without tt)
     *              [title] =>              (string)
     *              [trailerCreateDate] =>  (string iso date) 2024-11-17T13:16:18.708Z
     *              [trailerRuntime] =>     (int) (in seconds!)
     *              [playbackUrl] =>        (string) This url will playback in browser only)
     *              [thumbnailUrl] =>       (string) (thumbnail (140x207)image of the title)
     *              [releaseDate] =>        (string) (date string: December 4, 2024)
     *              [contentType] =>        (string ) like Trailer Season 1 [OV]
     *          )
     *  )
     */
    public function trendingVideo()
    {
        $trendingVideoResults = array();
        $query = <<<EOF
query TrendingVideo {
  trendingTitles(limit: 250) {
    titles {
      id
      titleText {
        text
      }
      releaseDate {
        displayableProperty {
          value {
            plainText
          }
        }
      }
      primaryImage {
        url
        width
        height
      }
      latestTrailer {
        createdDate
        id
        runtime {
          value
        }
        name {
          value
        }
      }
      
    }
  } 
}
EOF;
        $data = $this->graphql->query($query, "TrendingVideo");
        foreach ($data->trendingTitles->titles as $edge) {
            $thumbUrl = null;
            $videoId = isset($edge->latestTrailer->id) ?
                             str_replace('vi', '', $edge->latestTrailer->id) : null;
            if (empty($videoId)) {
                continue;
            }
            if (!empty($edge->primaryImage->url)) {
                $fullImageWidth = $edge->primaryImage->width;
                $fullImageHeight = $edge->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $thumbUrl = $img . $parameter;
            }
            $trendingVideoResults[] = array(
                'videoId' => $videoId,
                'titleId' => isset($edge->id) ?
                                   str_replace('tt', '', $edge->id) : null,
                'title' => isset($edge->titleText->text) ?
                                 $edge->titleText->text : null,
                'trailerCreateDate' => isset($edge->latestTrailer->createdDate) ?
                                             $edge->latestTrailer->createdDate : null,
                'trailerRuntime' => isset($edge->latestTrailer->runtime->value) ?
                                          $edge->latestTrailer->runtime->value : null,
                'playbackUrl' => !empty($videoId) ?
                                        'https://www.imdb.com/video/vi' . $videoId . '/' : null,
                'thumbnailUrl' => $thumbUrl,
                'releaseDate' => isset($edge->releaseDate->displayableProperty->value->plainText) ?
                                       $edge->releaseDate->displayableProperty->value->plainText : null,
                'contentType' => isset($edge->latestTrailer->name->value) ?
                                       $edge->latestTrailer->name->value : null
            );
        }
        return $trendingVideoResults;
    }

}
