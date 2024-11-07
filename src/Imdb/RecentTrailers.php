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
     *              [videoId] =>       (string) like vi4205431065
     *              [title] =>         (string)
     *              [runtime] =>       (int) (in seconds)
     *              [playbackUrl] =>   (string) This url will playback in browser only)
     *              [thumbnailUrl] =>  (string) (thumbnail image of the trailer)
     *              [releaseDate] =>   (string) (date string: December 4, 2024)
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
    }
  } 
}
EOF;
        $data = $this->graphql->query($query);
        foreach ($data->recentVideos->videos as $edge) {
            $id = isset($edge->id) ? $edge->id : null;
            $playbackUrl = !empty($id) ? 'https://www.imdb.com/video/' . $id . '/' : null;
            $list[] = array(
                'videoId' => $id,
                'title' => isset($edge->primaryTitle->titleText->text) ? $edge->primaryTitle->titleText->text : null,
                'runtime' => isset($edge->runtime->value) ? $edge->runtime->value : null,
                'playbackUrl' => $playbackUrl,
                'thumbnailUrl' => isset($edge->thumbnail->url) ? $edge->thumbnail->url : null,
                'releaseDate' => isset($edge->primaryTitle->releaseDate->displayableProperty->value->plainText) ?
                                       $edge->primaryTitle->releaseDate->displayableProperty->value->plainText : null
            );
        }
        return $list;
    }
}
