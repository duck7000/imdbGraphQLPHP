<?php

#############################################################################
# IMDBPHP6 comingSoon will get releases from https://www.imdb.com/calendar  #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Obtains information about upcoming movie releases as seen on IMDb
 * https://www.imdb.com/calendar
 * @author Ed (github user: duck7000)
 */
class Calendar extends MdbBase
{
    /**
     * Get upcoming movie releases as seen on IMDb
     * @parameter $region This defines which country's releases are returned like DE, NL, US
     * @parameter $type This defines which type is returned, MOVIE, TV or TV_EPISODE
     * @parameter $startDateOverride This defines the startDate override like +3 or -5 of default todays day
     * @parameter $filter This defines if disablePopularityFilter is set or not, set to false shows all releases,
     * true only returns populair releases so less results within the given date span
     * @parameter $thumb This defines if imgUrl contains thumb or full image, full image is large in size! (Thumb is 50x74)
     * there seems to be a limit of 100 titles but i did get more titles so i really don't know
     * @return array of array(title| string, imdbid|string, releaseDate|array, genres|array, cast|array, imgUrl|string)
     */
    public function comingSoon($region = "US", $type = "MOVIE", $startDateOverride = 0, $filter = "true", $thumb = true)
    {
        $startDate = date("Y-m-d");
        if ($startDateOverride != 0) {
            $startDate = date('Y-m-d', strtotime($startDateOverride . ' day', strtotime($startDate)) );
        }
        $futureDate = date('Y-m-d', strtotime('+1 year', strtotime($startDate)) );
        
        $query = <<<EOF
query {
    comingSoon(
      first: 9999
      comingSoonType: $type
      disablePopularityFilter: $filter
      regionOverride: "$region"
      releasingOnOrAfter: "$startDate"
      releasingOnOrBefore: "$futureDate"
      sort: {sortBy: RELEASE_DATE, sortOrder: ASC}) {
    edges {
      node {
        titleText {
          text
        }
        id
        releaseDate {
          day
          month
          year
        }
        titleGenres {
          genres {
            genre {
              text
            }
          }
        }
        principalCredits(filter: {categories: "cast"}) {
          credits {
            name {
              nameText {
                text
              }
            }
          }
        }
        primaryImage {
          url
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query);
        foreach ($data->comingSoon->edges as $edge) {
            $title = isset($edge->node->titleText->text) ? $edge->node->titleText->text : '';
            if ($title == '') {
                continue;
            }
            $imdbid = isset($edge->node->id) ? str_replace('tt', '', $edge->node->id) : '';
            
            //release date
            $day = isset($edge->node->releaseDate->day) ? $edge->node->releaseDate->day : null;
            $month = isset($edge->node->releaseDate->month) ? $edge->node->releaseDate->month : null;
            $year = isset($edge->node->releaseDate->year) ? $edge->node->releaseDate->year : null;
            $releaseDate = array(
                "day" => $day,
                "mon" => $month,
                "year" => $year
            );
            
            // Genres
            $genres = array();
            if (isset($edge->node->titleGenres)) {
                foreach ($edge->node->titleGenres->genres as $genre) {
                    $genres[] = $genre->genre->text;
                }
            }
            
            // Cast
            $cast = array();
            if (isset($edge->node->principalCredits[0])) {
                foreach ($edge->node->principalCredits[0]->credits as $credit) {
                    $cast[] = $credit->name->nameText->text;
                }
            }
            
            // image url
            $imgUrl = '';
            if (isset($edge->node->primaryImage->url) && $edge->node->primaryImage->url != null) {
                if ($thumb == true) {
                    $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                    $imgUrl = $img . 'QL75_UY74_CR41,0,50,74_.jpg';
                } else {
                    $imgUrl = $edge->node->primaryImage->url;
                }
            }
            
            $calendar[] = array(
                "title" => $title,
                "imdbid" => $imdbid,
                "releaseDate" => $releaseDate,
                "genres" => $genres,
                "cast" => $cast,
                "imgUrl" => $imgUrl
            );
        }
        return $calendar;
    }
}
