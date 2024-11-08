<?php

#############################################################################
# imdbGraphQLPHP RecentTrailers          https://www.imdb.com/trailers/     #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Obtains information about the latest trailers as seen on https://www.imdb.com/trailers/
 * max 100 items will be returned (more is not possible)
 * https://www.imdb.com/trailers/
 * @author Ed (github user: duck7000)
 */
class RecentTrailers extends MdbBase
{

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
     *              [thumbnailUrl] =>  (string) (thumbnail image of the trailer)
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
      }
      runtime {
        unit
        value
      }
      thumbnail {
        url
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
            $id = isset($edge->id) ? str_replace('vi', '', $edge->id) : null;
            $playbackUrl = !empty($id) ? 'https://www.imdb.com/video/vi' . $id . '/' : null;
            $list[] = array(
                'videoId' => $id,
                'titleId' => isset($edge->primaryTitle->id) ? str_replace('tt', '', $edge->primaryTitle->id) : null,
                'title' => isset($edge->primaryTitle->titleText->text) ? $edge->primaryTitle->titleText->text : null,
                'runtime' => isset($edge->runtime->value) ? $edge->runtime->value : null,
                'playbackUrl' => $playbackUrl,
                'thumbnailUrl' => isset($edge->thumbnail->url) ? $edge->thumbnail->url : null,
                'releaseDate' => isset($edge->primaryTitle->releaseDate->displayableProperty->value->plainText) ?
                                       $edge->primaryTitle->releaseDate->displayableProperty->value->plainText : null,
                'contentType' => isset($edge->name->value) ? $edge->name->value : null
            );
        }
        return $list;
    }

}
