<?php

#############################################################################
# imdbGraphQLPHP comingSoon                                                 #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * Obtains information about upcoming movie releases as seen on IMDb
 * https://www.imdb.com/calendar
 * @author Ed (github user: duck7000)
 */
class Calendar extends MdbBase
{
    protected $imageFunctions;
    protected $newImageWidth;
    protected $newImageHeight;
    protected $calendar = array();

    /**
     * @param Config $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(Config $config = null, LoggerInterface $logger = null, CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->imageFunctions = new Image();
        $this->newImageWidth = $this->config->calendarThumbnailWidth;
        $this->newImageHeight = $this->config->calendarThumbnailHeight;
    }

    /**
     * Get upcoming movie releases as seen on IMDb
     * @parameter $region This defines which country's releases are returned like DE, NL, US
     * @parameter $type This defines which type is returned, MOVIE, TV or TV_EPISODE
     * @parameter $startDateOverride This defines the startDate override like +3 or -5 of default todays day
     * @parameter $filter This defines if disablePopularityFilter is set or not, set to false shows all releases,
     * true only returns populair releases so less results within the given date span
     * there seems to be a limit of 100 titles but i did get more titles so i really don't know
     * @return array categorized by release date ASC
     *      [11-15-2024] => (array)
     *          [0] => Array
     *              [title] =>  (string) Red One
     *              [imdbid] => (string) 14948432
     *              [genres] => (array)
     *                  [0] =>      (string) Action
     *                  [1] =>      (string) Adventure
     *              [cast] => Array
     *                  [0] =>      (string) Dwayne Johnson
     *                  [1] =>      (string) Chris Evans
     *              [imgUrl] => (string) https://m.media-amazon.com/images/M/MV5Bc@._V1_QL75_SX50_CR0,0,140,207_.jpg
     */
    public function comingSoon($region = "US", $type = "MOVIE", $startDateOverride = 0, $filter = "true")
    {
        $startDate = date("Y-m-d");
        if ($startDateOverride != 0) {
            $startDate = date('Y-m-d', strtotime($startDateOverride . ' day', strtotime($startDate)) );
        }
        $futureDate = date('Y-m-d', strtotime('+1 year', strtotime($startDate)) );
        
        $query = <<<EOF
query ComingSoon {
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
          width
          height
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "ComingSoon");
        foreach ($data->comingSoon->edges as $edge) {
            $title = isset($edge->node->titleText->text) ? $edge->node->titleText->text : null;
            if ($title === null || stripos($title, "Untitled IFC") !== false) {
                continue;
            }
            $imdbid = isset($edge->node->id) ? str_replace('tt', '', $edge->node->id) : null;

            //release date
            $releaseDate = isset($edge->node->releaseDate->month) ? $edge->node->releaseDate->month . '-' : null;
            $releaseDate .= isset($edge->node->releaseDate->day) ? $edge->node->releaseDate->day . '-' : null;
            $releaseDate .= isset($edge->node->releaseDate->year) ? $edge->node->releaseDate->year : null;

            // Genres
            $genres = array();
            if (!empty($edge->node->titleGenres->genres)) {
                foreach ($edge->node->titleGenres->genres as $genre) {
                    if (!empty($genre->genre->text)) {
                        $genres[] = $genre->genre->text;
                    }
                }
            }

            // Cast
            $cast = array();
            if (!empty($edge->node->principalCredits[0]->credits)) {
                foreach ($edge->node->principalCredits[0]->credits as $credit) {
                    if (!empty($credit->name->nameText->text)) {
                        $cast[] = $credit->name->nameText->text;
                    }
                }
            }

            // image url
            $imgUrl = null;
            if (!empty($edge->node->primaryImage->url)) {
                $fullImageWidth = $edge->node->primaryImage->width;
                $fullImageHeight = $edge->node->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $imgUrl = $img . $parameter;
            }

            $this->calendar[$releaseDate][] = array(
                "title" => $title,
                "imdbid" => $imdbid,
                "genres" => $genres,
                "cast" => $cast,
                "imgUrl" => $imgUrl
            );
        }
        return $this->calendar;
    }
}
