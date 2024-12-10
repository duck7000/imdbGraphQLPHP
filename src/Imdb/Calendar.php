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
        $calendar = array();
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
            $title = isset($edge->node->titleText->text) ?
                           $edge->node->titleText->text : null;
            if ($title === null) {
                continue;
            }
            //release date
            $dateParts = array(
                'month' => isset($edge->node->releaseDate->month) ?
                                 $edge->node->releaseDate->month : null,
                'day' => isset($edge->node->releaseDate->day) ?
                               $edge->node->releaseDate->day : null,
                'year' => isset($edge->node->releaseDate->year) ?
                                $edge->node->releaseDate->year : null
            );
            $releaseDate = $this->buildDateString($dateParts);
            if ($releaseDate === false) {
                continue;
            }
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
            $calendar[$releaseDate][] = array(
                'title' => $title,
                'imdbid' => isset($edge->node->id) ?
                                  str_replace('tt', '', $edge->node->id) : null,
                'genres' => $genres,
                'cast' => $cast,
                'imgUrl' => $imgUrl
            );
        }
        return $calendar;
    }

    /**
     * Get upcoming releases from big streaming providers for current month.
     * See https://www.imdb.com/list/ls549391228/ (Netflix)
     * @parameter $listProviderId This is the streaming provider list id like "549391228" (without ls)
     * Possible providerIds:
     *      549391228 (Netflix)
     *      549615961 (HBO MAX)
     *      549641648 (Prime Video)
     *      549359815 (Disney+)
     *      549124072 (Hulu)
     *      549641648 (Amazon Prime)
     *      549617029 (Paramount+)
     *      544306775 (TV and Streaming Calendar)
     * @config options
     *      $streamSortBy, $streamSortOrder, $calendarThumbnailWidth, $calendarThumbnailHeight
     * 
     * @return array ()
     *  [listId] =>                 (string) 549391228 (without ls)
     *  [listName] =>               (string) What's New on Netflix in November 2024
     *  [listCreateDate] =>         (string)2024-10-23T21:14:59Z
     *  [listLastModifiedDate ] =>  (string)2024-10-23T21:42:30Z
     *  [items] => Array()
     *      [0] => Array()
     *          [id] =>             (string) 33130884 (without tt)
     *          [title] =>          (string) Barbie Mysteries: The Great Horse Chase
     *          [type] =>           (string) TV Series
     *          [year] =>           (int) 2024
     *          [description] =>    (string) Season 1 Available November 1
     *          [runtime] =>        (int) 1320 (Seconds!)
     *          [rating] =>         (float) 6.7
     *          [votes] =>          (int) 36
     *          [metacritic] =>     (int) 75
     *          [plot] =>           (string) Barbie "Brooklyn" Roberts and Barbie "Malibu" Roberts embark on an adventure-packed journey across Europe to rescue two stolen horses.
     *          [thumbUrl] =>       (string) https://m.media-amazon.com/images/M/MV5BOTg3MDg2ZTYtMjgzNy00MmViLWJjOWItYzQ4YWNmOWQ1ZGM0XkEyXkFqcGc@._V1_QL75_SX140_CR0,2,140,207_.jpg
     *          [credits] => Array() categorized by index like Stars and Director max 3 elements in each category
     *              [Director] => Array()
     *                  [0] => Array()
     *                      [nameId] => (string) 4638466 (without nm)
     *                      [name] =>   (string) Joanna Pardos
     *              [Stars] => Array()
     *                  [0] => Array()
     *                      [nameId] => (string) 0046033 (without nm)
     *                      [name] =>   (string) Diedrich Bader
     *                  [1] => Array()
     *                      [nameId] => (string) 1312566 (without nm)
     *                      [name] =>   (string) Kari Wahlgren
     *                  [2] => Array()
     *                      [nameId] => (string) 1293885 (without nm)
     *                      [name] =>   (string) Bobby Moynihan
     */
    public function comingSoonStreaming($listProviderId)
    {
        $calendarStreaming = array();
        $sortBy = $this->config->streamSortBy;
        $sortOrder = $this->config->streamSortOrder;

        $query = <<<EOF
query ComingSoonStreaming {
  list(id: "ls$listProviderId") {
    createdDate
    id
    lastModifiedDate
    name {
      originalText
    }
    items(
      first: 250
      sort: {by: $sortBy, order: $sortOrder}
    ) {
      edges {
        node {
          description {
            originalText {
              plainText
            }
          }
          item {
            ... on Title {
              id
              titleText {
                text
              }
              releaseYear {
                year
              }
              titleType {
                text
              }
              runtime {
                seconds
              }
              ratingsSummary {
                aggregateRating
                voteCount
              }
              metacritic {
                metascore {
                  score
                }
              }
              plot {
                plotText {
                  plainText
                }
              }
              principalCredits(filter: {categories: ["cast", "director"]}) {
                category {
                  text
                }
                credits(limit: 3) {
                  name {
                    id
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
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "ComingSoonStreaming", ["id" => "ls$listProviderId"]);
        $items = array();
        foreach ($data->list->items->edges as $edge) {

            // image url
            $imgUrl = null;
            if (!empty($edge->node->item->primaryImage->url)) {
                $fullImageWidth = $edge->node->item->primaryImage->width;
                $fullImageHeight = $edge->node->item->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->node->item->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $imgUrl = $img . $parameter;
            }

            // PrincipalCredits
            $credits = array();
            if (!empty($edge->node->item->principalCredits)) {
                foreach ($edge->node->item->principalCredits as $principalCredit) {
                    $category = $principalCredit->category->text;
                    $temp = array();
                    foreach ($principalCredit->credits as $credit) {
                        $temp[] = array(
                            'nameId' => isset($credit->name->id) ?
                                              str_replace('nm', '', $credit->name->id) : null,
                            'name' => isset($credit->name->nameText->text) ?
                                            $credit->name->nameText->text : null
                        );
                    }
                    $credits[$category] = $temp;
                }
            }

            $items[] = array(
                'id' => isset($edge->node->item->id) ?
                              str_replace('tt', '', $edge->node->item->id) : null,
                'title' => isset($edge->node->item->titleText->text) ?
                                 $edge->node->item->titleText->text : null,
                'type' => isset($edge->node->item->titleType->text) ?
                                $edge->node->item->titleType->text : null,
                'year' => isset($edge->node->item->releaseYear->year) ?
                                $edge->node->item->releaseYear->year : null,
                'description' => isset($edge->node->description->originalText->plainText) ?
                                       $edge->node->description->originalText->plainText : null,
                'runtime' => isset($edge->node->item->runtime->seconds) ?
                                   $edge->node->item->runtime->seconds : null,
                'rating' => isset($edge->node->item->ratingsSummary->aggregateRating) ?
                                  $edge->node->item->ratingsSummary->aggregateRating : null,
                'votes' => isset($edge->node->item->ratingsSummary->voteCount) ?
                                 $edge->node->item->ratingsSummary->voteCount : null,
                'metacritic' => isset($edge->node->item->metacritic->metascore->score) ?
                                      $edge->node->item->metacritic->metascore->score : null,
                'plot' => isset($edge->node->item->plot->plotText->plainText) ?
                                $edge->node->item->plot->plotText->plainText : null,
                'thumbUrl' => $imgUrl,
                'credits' => $credits
            );
        }
        $calendarStreaming = array(
            'listId' => isset($data->list->id) ?
                              str_replace('ls', '', $data->list->id) : null,
            'listName' => isset($data->list->name->originalText) ?
                                $data->list->name->originalText : null,
            'listCreateDate' => isset($data->list->createdDate) ?
                                      $data->list->createdDate : null,
            'listLastModifiedDate ' => isset($data->list->lastModifiedDate) ?
                                             $data->list->lastModifiedDate : null,
            'items' => $items
        );
        return $calendarStreaming;
    }

}
