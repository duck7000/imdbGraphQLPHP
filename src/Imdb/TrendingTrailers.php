<?php

#############################################################################
# imdbGraphQLPHP TrendingTrailers        https://www.imdb.com/trailers/     #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Imdb\Image;

/**
 * Obtains information about trending trailers as seen on https://www.imdb.com/trailers/
 * max 250 items will be returned
 * @note Titles without a videoId will be skipped, so can be less than 250
 * https://www.imdb.com/trailers/
 * @author Ed (github user: duck7000)
 */
class TrendingTrailers extends MdbBase
{

    protected $imageFunctions;

    /**
     * Get trending trailers as seen on IMDb https://www.imdb.com/trailers/
     * @return
     * Array
     *   (
     *      [0] => Array
     *          (
     *              [videoId] =>       (string) (without vi)
     *              [titleId] =>       (string) (without tt)
     *              [title] =>         (string)
     *              [runtime] =>       (int) (in seconds)
     *              [playbackUrl] =>   (string) This url will playback in browser only)
     *              [thumbnailUrl] =>  (string) (thumbnail (140x207)image of the title)
     *              [releaseDate] =>   (string) (date string: December 4, 2024)
     *              [contentType] =>   (string ) like Trailer Season 1 [OV]
     *          )
     *  )
     */
    public function trendingVideo()
    {
        $query = <<<EOF
query {
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
        $data = $this->graphql->query($query);
        foreach ($data->trendingTitles->titles as $edge) {
            $thumbUrl = null;
            $videoId = isset($edge->latestTrailer->id) ? str_replace('vi', '', $edge->latestTrailer->id) : null;
            if (empty($videoId)) {
                continue;
            }
            if (!empty($edge->primaryImage->url)) {
                $this->imageFunctions = new Image();
                $fullImageWidth = $edge->primaryImage->width;
                $fullImageHeight = $edge->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, 140, 207);
                $thumbUrl = $img . $parameter;
            }
            $list[] = array(
                'videoId' => $videoId,
                'titleId' => isset($edge->id) ? str_replace('tt', '', $edge->id) : null,
                'title' => isset($edge->titleText->text) ? $edge->titleText->text : null,
                'runtime' => isset($edge->latestTrailer->runtime->value) ? $edge->latestTrailer->runtime->value : null,
                'playbackUrl' => !empty($videoId) ? 'https://www.imdb.com/video/vi' . $videoId . '/' : null,
                'thumbnailUrl' => $thumbUrl,
                'releaseDate' => isset($edge->releaseDate->displayableProperty->value->plainText) ?
                                       $edge->releaseDate->displayableProperty->value->plainText : null,
                'contentType' => isset($edge->latestTrailer->name->value) ? $edge->latestTrailer->name->value : null
            );
        }
        return $list;
    }

}
