<?php

#############################################################################
# imdbGraphQLPHP Chart                       https://www.imdb.com/chart     #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Obtains information about top250 lists as seen on IMDb
 * https://www.imdb.com/chart
 * @author Ed (github user: duck7000)
 */
class Chart extends MdbBase
{
    /**
     * Get top250 lists as seen on IMDb https://www.imdb.com/chart
     * @parameter $listType This defines different kind of lists like top250 Movie or TV
     * possible values for $listType:
     *  BOTTOM_100
     *      Overall IMDb Bottom 100 Feature List
     *  TOP_50_BENGALI
     *      Top 50 Bengali Feature List
     *  TOP_50_MALAYALAM
     *      Top 50 Malayalam Feature List
     *  TOP_50_TAMIL
     *      Top 50 Tamil Feature List
     *  TOP_50_TELUGU
     *      Top 50 Telugu Feature List
     *  TOP_250
     *      Overall IMDb Top 250 Feature List
     *  TOP_250_ENGLISH
     *      Top 250 English Feature List
     *  TOP_250_INDIA
     *      Top 250 Indian Feature List
     *  TOP_250_TV
     *      Overall IMDb Top 250 TV List
     * @parameter $thumb This defines if imgUrl contains thumb or full image, full image is large in size! (Thumb is 140x207)
     * @return
     * Array
     *   (
     *      [0] => Array
     *          (
     *              [title] =>          (string) Breaking Bad
     *              [imdbid] =>         (string) 0903747
     *              [year] =>           (int)2008
     *              [rank] =>           (int)1
     *              [rating] =>         (float) 9.5
     *              [votes] =>          (int)2178109
     *              [runtimeSeconds] => (int)2700
     *              [runtimeText] =>    (string) 45m
     *              [imgUrl] =>         (string) https://m.media-amazon.com/images/M/MV5BYmQ4YWMxYjUtNjZmYi00MDQ1LWFjMjMtNjA5ZDdiYjdiODU5XkEyXkFqcGdeQXVyMTMzNDExODE5._V1_QL75_UX140_.jpg
     *          )
     *  )
     */
    public function top250List($listType = "TOP_250", $thumb = true)
    {
        $query = <<<EOF
query {
  titleChartRankings(
    first: 250
    input: {rankingsChartType: $listType}
  ) {
    edges {
      node{
        item {
          id
          titleText {
            text
          }
          releaseYear {
            year
          }
          ratingsSummary {
            topRanking {
              rank
            }
            aggregateRating
            voteCount
          }
          primaryImage {
            url
          }
          runtime {
            seconds
            displayableProperty {
              value {
                plainText
              }
            }
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query);

        foreach ($data->titleChartRankings->edges as $edge) {
            $title = isset($edge->node->item->titleText->text) ? $edge->node->item->titleText->text : null;
            $imdbid = isset($edge->node->item->id) ? str_replace('tt', '', $edge->node->item->id) : null;
            $year = isset($edge->node->item->releaseYear->year) ? $edge->node->item->releaseYear->year : null;
            $rank = isset($edge->node->item->ratingsSummary->topRanking->rank) ? $edge->node->item->ratingsSummary->topRanking->rank : null;
            $rating = isset($edge->node->item->ratingsSummary->aggregateRating) ? $edge->node->item->ratingsSummary->aggregateRating : null;
            $votes = isset($edge->node->item->ratingsSummary->voteCount) ? $edge->node->item->ratingsSummary->voteCount : null;
            $runtimeSeconds = isset($edge->node->item->runtime->seconds) ? $edge->node->item->runtime->seconds : null;
            $runtimeText = isset($edge->node->item->runtime->displayableProperty->value->plainText) ?
                                 $edge->node->item->runtime->displayableProperty->value->plainText : null;

            // image url
            $imgUrl = null;
            if (!empty($edge->node->item->primaryImage->url)) {
                if ($thumb == true) {
                    $img = str_replace('.jpg', '', $edge->node->item->primaryImage->url);
                    $imgUrl = $img . 'QL75_UX140_.jpg';
                } else {
                    $imgUrl = $edge->node->item->primaryImage->url;
                }
            }

            $list[] = array(
                'title' => $title,
                'imdbid' => $imdbid,
                'year' => $year,
                'rank' => $rank,
                'rating' => $rating,
                'votes' => $votes,
                'runtimeSeconds' => $runtimeSeconds,
                'runtimeText' => $runtimeText,
                'imgUrl' => $imgUrl
            );
        }
        return $list;
    }
}
