<?php

#############################################################################
# imdbGraphQLPHP RecentTrailers          https://www.imdb.com/trailers/     #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Imdb\Image;

/**
 * Obtains information about the latest trailers as seen on https://www.imdb.com/trailers/
 * max 100 items will be returned (more is not possible)
 * https://www.imdb.com/trailers/
 * @author Ed (github user: duck7000)
 */
class RecentTrailers extends MdbBase
{

    protected $imageFunctions;

    /**
     * Get the latest trailers as seen on IMDb https://www.imdb.com/trailers/
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
     *              [thumbnailUrl] =>  (string) (thumbnail (140x207) image of the title)
     *              [releaseDate] =>   (string) (date string: December 4, 2024)
     *              [contentType] =>   (string ) like Trailer Season 1 [OV]
     *          )
     *  )
     */
    public function recentVideo()
    {
        $query = <<<EOF
query {
  recentVideos(
    limit: 100
    queryFilter: {contentTypes: TRAILER}
  ) {
    videos {
      id
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
        $data = $this->graphql->query($query);
        foreach ($data->recentVideos->videos as $edge) {
            $thumbUrl = null;
            $videoId = isset($edge->id) ? str_replace('vi', '', $edge->id) : null;
            if (!empty($edge->primaryTitle->primaryImage->url)) {
                $this->imageFunctions = new Image();
                $fullImageWidth = $edge->primaryTitle->primaryImage->width;
                $fullImageHeight = $edge->primaryTitle->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->primaryTitle->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, 140, 207);
                $thumbUrl = $img . $parameter;
            }
            $list[] = array(
                'videoId' => $videoId,
                'titleId' => isset($edge->primaryTitle->id) ? str_replace('tt', '', $edge->primaryTitle->id) : null,
                'title' => isset($edge->primaryTitle->titleText->text) ? $edge->primaryTitle->titleText->text : null,
                'runtime' => isset($edge->runtime->value) ? $edge->runtime->value : null,
                'playbackUrl' => !empty($videoId) ? 'https://www.imdb.com/video/vi' . $videoId . '/' : null,
                'thumbnailUrl' => $thumbUrl,
                'releaseDate' => isset($edge->primaryTitle->releaseDate->displayableProperty->value->plainText) ?
                                       $edge->primaryTitle->releaseDate->displayableProperty->value->plainText : null,
                'contentType' => isset($edge->name->value) ? $edge->name->value : null
            );
        }
        return $list;
    }

}
