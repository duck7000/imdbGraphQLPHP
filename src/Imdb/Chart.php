<?php

#############################################################################
# imdbGraphQLPHP Chart                       https://www.imdb.com/chart     #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * Obtains information about chart lists as seen on IMDb
 * https://www.imdb.com/chart
 * @Note thumbnail width and height are set in config, one setting for all methods!
 * @author Ed (github user: duck7000)
 */
class Chart extends MdbBase
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
     * Get top250 titles lists as seen on IMDb https://www.imdb.com/chart
     * @parameter $listType This defines different kind of lists like top250 Movie or TV
     * possible values for $listType:
     *  BOTTOM_100
     *      Overall IMDb Bottom 100 Feature List
     *  TOP_250
     *      Overall IMDb Top 250 Feature List
     *  TOP_250_ENGLISH
     *      Top 250 English Feature List
     *  TOP_250_TV
     *      Overall IMDb Top 250 TV List
     * @return
     * Array
     *   (
     *      [0] => Array
     *          (
     *              [title] =>          (string) Breaking Bad
     *              [imdbid] =>         (string) 0903747
     *              [year] =>           (int) 2008
     *              [rank] =>           (int) 1
     *              [rating] =>         (float) 9.5
     *              [votes] =>          (int) 2178109
     *              [runtimeSeconds] => (int) 2700
     *              [runtimeText] =>    (string) 45m
     *              [imgUrl] =>         (string) (140x207 set in config)
     *          )
     *  )
     */
    public function top250Title($listType = "TOP_250")
    {
        $top250TitleResults = array();
        $query = <<<EOF
query Top250Title {
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
            width
            height
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
        $data = $this->graphql->query($query, "Top250Title");
        foreach ($data->titleChartRankings->edges as $edge) {
            $thumbUrl = null;
            if (!empty($edge->node->item->primaryImage->url)) {
                $fullImageWidth = $edge->node->item->primaryImage->width;
                $fullImageHeight = $edge->node->item->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->node->item->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $thumbUrl = $img . $parameter;
            }
            $top250TitleResults[] = array(
                'title' => isset($edge->node->item->titleText->text) ?
                                 $edge->node->item->titleText->text : null,
                'imdbid' => isset($edge->node->item->id) ?
                                  str_replace('tt', '', $edge->node->item->id) : null,
                'year' => isset($edge->node->item->releaseYear->year) ?
                                $edge->node->item->releaseYear->year : null,
                'rank' => isset($edge->node->item->ratingsSummary->topRanking->rank) ?
                                $edge->node->item->ratingsSummary->topRanking->rank : null,
                'rating' => isset($edge->node->item->ratingsSummary->aggregateRating) ?
                                  $edge->node->item->ratingsSummary->aggregateRating : null,
                'votes' => isset($edge->node->item->ratingsSummary->voteCount) ?
                                 $edge->node->item->ratingsSummary->voteCount : null,
                'runtimeSeconds' => isset($edge->node->item->runtime->seconds) ?
                                          $edge->node->item->runtime->seconds : null,
                'runtimeText' => isset($edge->node->item->runtime->displayableProperty->value->plainText) ?
                                       $edge->node->item->runtime->displayableProperty->value->plainText : null,
                'imgUrl' => $thumbUrl
            );
        }
        return $top250TitleResults;
    }

    /**
     * Get top250 Names lists (Not seen on IMDb afaik)
     * @return
     * Array
     *   (
     *      [0] => Array
     *          (
     *              [name] =>       (string) jenifer lopez
     *              [imdbid] =>     (string) 0903747
     *              [rank] =>       (int)1
     *              [credits] =>    (array)
     *                  [0] => Actress
     *                  [1] => Producer
     *                  [2] => Director
     *                  [3] => Writer
     *                  [4] => Self
     *                  [5] => Thanks
     *              [knownFor] =>   (array)
     *                  [id] => 2258337
     *                  [title] => Eega
     *                  [year] => 2012
     *              [imgUrl] =>     (string) (140x207 set in config)
     *          )
     *  )
     */
    public function top250Name()
    {
        $top250NameResults = array();
        $query = <<<EOF
query Top250Name {
  nameChartRankings(
  first: 250
  input: {rankingsChartType: INDIA_STAR_METER}
  ) {
    edges {
      node {
        rank
        item {
          nameText {
            text
          }
          id
          creditSummary {
            categories {
              category {
                text
              }
            }
          }
          knownFor(first: 1) {
            edges {
              node {
                title {
                  id
                  titleText {
                    text
                  }
                  releaseYear {
                    year
                  }
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
EOF;
        $data = $this->graphql->query($query, "Top250Name");
        foreach ($data->nameChartRankings->edges as $edge) {
            $thumbUrl = null;
            $credits = array();
            $knownFor = array();
            if (!empty($edge->node->item->primaryImage->url)) {
                $fullImageWidth = $edge->node->item->primaryImage->width;
                $fullImageHeight = $edge->node->item->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->node->item->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $thumbUrl = $img . $parameter;
            }
            if (!empty($edge->node->item->knownFor->edges)) {
                $knownFor = array(
                    'id' => isset($edge->node->item->knownFor->edges[0]->node->title->id) ?
                                  str_replace('tt', '', $edge->node->item->knownFor->edges[0]->node->title->id) : null,
                    'title' => isset($edge->node->item->knownFor->edges[0]->node->title->titleText->text) ?
                                     $edge->node->item->knownFor->edges[0]->node->title->titleText->text : null,
                    'year' => isset($edge->node->item->knownFor->edges[0]->node->title->releaseYear->year) ?
                                    $edge->node->item->knownFor->edges[0]->node->title->releaseYear->year : null
                );
            }
            if (!empty($edge->node->item->creditSummary->categories)) {
                foreach ($edge->node->item->creditSummary->categories as $item) {
                    if (!empty($item->category->text)) {
                        $credits[] = $item->category->text;
                    }
                }
            }
            $top250NameResults[] = array(
                'name' => isset($edge->node->item->nameText->text) ?
                                $edge->node->item->nameText->text : null,
                'imdbid' => isset($edge->node->item->id) ?
                                  str_replace('nm', '', $edge->node->item->id) : null,
                'rank' => isset($edge->node->rank) ?
                                $edge->node->rank : null,
                'credits' => $credits,
                'knownFor' => $knownFor,
                'imgUrl' => $thumbUrl
            );
        }
        return $top250NameResults;
    }

    /**
     * Get most popular Names lists as seen on https://imdb.com/chart/starmeter
     * @return
     * Array
     *   (
     *      [0] => Array
     *          (
     *              [name] =>       (string) jenifer lopez
     *              [imdbid] =>     (string) 0903747
     *              [rank] =>       (int)1
     *              [credits] =>    (array)
     *                  [0] => Actress
     *                  [1] => Producer
     *                  [2] => Director
     *                  [3] => Writer
     *                  [4] => Self
     *                  [5] => Thanks
     *              [knownFor] =>   (array)
     *                  [id] => 2258337
     *                  [title] => Eega
     *                  [year] => 2012
     *              [imgUrl] =>     (string) (140x207 set in config)
     *          )
     *  )
     */
    public function mostPopularName()
    {
        $mostPopularNameResults = array();
        $query = <<<EOF
query MostPopularName {
  chartNames(
    first: 100
    chart: {chartType: MOST_POPULAR_NAMES}
    sort: {sortBy: POPULARITY, sortOrder: ASC}
  ) {
    edges {
      node {
        id
        nameText {
          text
        }
        creditCategories {
          category {
            text
          }
        }
        knownFor(first: 1) {
          edges {
            node {
              title {
                id
                titleText {
                  text
                }
                releaseYear{
                  year
                }
              }
            }
          }
        }
        primaryImage {
          url
          width
          height
        }
        meterRanking {
          currentRank
          rankChange {
            difference
            changeDirection
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "MostPopularName");
        foreach ($data->chartNames->edges as $edge) {
            $thumbUrl = null;
            $credits = array();
            $knownFor = array();
            if (!empty($edge->node->primaryImage->url)) {
                $fullImageWidth = $edge->node->primaryImage->width;
                $fullImageHeight = $edge->node->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $thumbUrl = $img . $parameter;
            }
            if (!empty($edge->node->knownFor->edges)) {
                $knownFor = array(
                    'id' => isset($edge->node->knownFor->edges[0]->node->title->id) ?
                                  str_replace('tt', '', $edge->node->knownFor->edges[0]->node->title->id) : null,
                    'title' => isset($edge->node->knownFor->edges[0]->node->title->titleText->text) ?
                                     $edge->node->knownFor->edges[0]->node->title->titleText->text : null,
                    'year' => isset($edge->node->knownFor->edges[0]->node->title->releaseYear->year) ?
                                    $edge->node->knownFor->edges[0]->node->title->releaseYear->year : null
                );
            }
            if (!empty($edge->node->creditCategories)) {
                foreach ($edge->node->creditCategories as $item) {
                    if (!empty($item->category->text)) {
                        $credits[] = $item->category->text;
                    }
                }
            }
            $mostPopularNameResults[] = array(
                'name' => isset($edge->node->nameText->text) ?
                                $edge->node->nameText->text : null,
                'imdbid' => isset($edge->node->id) ?
                                  str_replace('nm', '', $edge->node->id) : null,
                'rank' => isset($edge->node->rank) ?
                                $edge->node->rank : null,
                'credits' => $credits,
                'knownFor' => $knownFor,
                'imgUrl' => $thumbUrl
            );
        }
        return $mostPopularNameResults;
    }

    /**
     * Get most popular Titles lists as seen on https://imdb.com/chart/moviemeter
     * @parameter $genreId This filters the results on a genreId like "Horror"
     * GenreIDs: Action, Adult, Adventure, Animation, Biography, Comedy, Crime,
     *           Documentary, Drama, Family, Fantasy, Film-Noir, Game-Show,
     *           History, Horror, Music, Musical, Mystery, News, Reality-TV,
     *           Romance, Sci-Fi, Short, Sport, Talk-Show, Thriller, War, Western
     * 
     * @parameter $listType This defines different kind of lists like Movie or TV
     * possible values for $listType:
     *  LOWEST_RATED_MOVIES
     *      Lowest Rated IMDb Bottom List
     *  MOST_POPULAR_MOVIES
     *      Most Popular IMDb Movies List
     *  MOST_POPULAR_TV_SHOWS
     *      Most Popular IMDb TV List
     *  TOP_RATED_MOVIES
     *      Top Rated IMDb Movies List
     *  TOP_RATED_ENGLISH_MOVIES
     *      Top Rated English IMDb Movies List
     *  TOP_RATED_TV_SHOWS
     *      Top Rated IMDb TV List
     * 
     * @return
     * Array
     *   (
     *      [0] => Array
     *          (
     *          [title] =>              (string) The Substance
     *          [imdbid] =>             (string) 17526714
     *          [year] =>               (int) 2024
     *          [runtimeSeconds] =>     (int) 8460
     *          [runtimeText] =>        (string) 2h 21m
     *          [rank] =>               (int) 1
     *          [genre] =>              (array) every index an genre
     *          [rating] =>             (float) 7.5
     *          [votes] =>              (int) 124556
     *          [imgUrl] =>             (string) (140x207 set in config)
     *          )
     *  )
     */
    public function mostPopularTitle($listType = "MOST_POPULAR_MOVIES", $genreId = null)
    {
        $mostPopularTitleResults = array();
        $filter = '';
        if (!empty($genreId)) {
            $filter = 'genreConstraint:{allGenreIds:["' . $genreId . '"]}';
        }

        $query = <<<EOF
query MostPopularTitle {
  chartTitles(
    first: 9999
    chart: {chartType: $listType}
    sort: {sortBy: RANKING, sortOrder: ASC}
    filter:{explicitContentConstraint:{explicitContentFilter:INCLUDE_ADULT}$filter}
  ) {
    edges {
      currentRank
      node {
        id
        titleGenres {
          genres {
            genre {
              text
            }
          }
        }
        titleText {
          text
        }
        releaseYear {
          year
        }
        runtime {
          seconds
          displayableProperty {
            value {
              plainText
            }
          }
        }
        ratingsSummary {
          aggregateRating
          voteCount
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
        $data = $this->graphql->query($query, "MostPopularTitle");
        foreach ($data->chartTitles->edges as $edge) {
            $thumbUrl = null;
            if (!empty($edge->node->primaryImage->url)) {
                $fullImageWidth = $edge->node->primaryImage->width;
                $fullImageHeight = $edge->node->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $thumbUrl = $img . $parameter;
            }
            $genres = array();
            if (!empty($edge->node->titleGenres->genres)) {
                foreach ($edge->node->titleGenres->genres as $genre) {
                    if (!empty($genre->genre->text)) {
                        $genres[] = $genre->genre->text;
                    }
                }
            }
            $mostPopularTitleResults[] = array(
                'title' => isset($edge->node->titleText->text) ?
                                 $edge->node->titleText->text : null,
                'imdbid' => isset($edge->node->id) ?
                                  str_replace('tt', '', $edge->node->id) : null,
                'year' => isset($edge->node->releaseYear->year) ?
                                $edge->node->releaseYear->year : null,
                'runtimeSeconds' => isset($edge->node->runtime->seconds) ?
                                          $edge->node->runtime->seconds : null,
                'runtimeText' => isset($edge->node->runtime->displayableProperty->value->plainText) ?
                                       $edge->node->runtime->displayableProperty->value->plainText : null,
                'rank' => isset($edge->currentRank) ?
                                $edge->currentRank : null,
                'genre' => $genres,
                'rating' => isset($edge->node->ratingsSummary->aggregateRating) ?
                                  $edge->node->ratingsSummary->aggregateRating : null,
                'votes' => isset($edge->node->ratingsSummary->voteCount) ?
                                 $edge->node->ratingsSummary->voteCount : null,
                'imgUrl' => $thumbUrl
            );
        }
        return $mostPopularTitleResults;
    }

    /**
     * Get topBoxWeekend list as seen on https://www.imdb.com/chart/boxoffice/
     * max 10 results! more is not possible
     * Thumbnail is set in config for the whole class, default 140x207
     * @return
     * Array
     *      [weekendStartDate] => 2024-11-29
     *      [weekendEndDate] => 2024-12-01
     *      [titles] => Array
     *          [0] => Array()
     *              [title] =>                  (string)
     *              [id] =>                     (string) 13622970
     *              [rating] =>                 (float) 7.1
     *              [votes] =>                  (int) 17669
     *              [LifetimeGrossAmount] =>    (int) 221000000
     *              [LifetimeGrossCurrency] =>  (string) USD
     *              [weekendGrossAmount] =>     (int) 135500000
     *              [weekendGrossCurrency] =>   (string) USD
     *              [weeksReleased] =>          (int)
     *              [imgUrl] =>                 (string)
     */
    public function topBoxOffice()
    {
        $boxOfficeResults = array();
        $query = <<<EOF
query BoxOffice{
  boxOfficeWeekendChart(limit: 10) {
    entries {
      title {
        id
        titleText {
          text
        }
        releaseDate {
          day
          month
          year
        }
        ratingsSummary {
          aggregateRating
          voteCount
        }
        primaryImage {
          url
          width
          height
        }
        lifetimeGross(boxOfficeArea: DOMESTIC) {
          total {
            amount
            currency
          }
        }
      }
      weekendGross {
        total {
          amount
          currency
        }
      }
    }
    weekendEndDate
    weekendStartDate
  }
}
EOF;
        $data = $this->graphql->query($query, "BoxOffice");
        foreach ($data->boxOfficeWeekendChart->entries as $edge) {
            $thumbUrl = null;
            if (!empty($edge->title->primaryImage->url)) {
                $fullImageWidth = $edge->title->primaryImage->width;
                $fullImageHeight = $edge->title->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->title->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $thumbUrl = $img . $parameter;
            }
            $weeks = null;
            if (!empty($edge->title->releaseDate->day) && !empty($edge->title->releaseDate->month) && !empty($edge->title->releaseDate->year)) {
                $startDate = $edge->title->releaseDate->month . '/' .
                             $edge->title->releaseDate->day . '/' .
                             $edge->title->releaseDate->year;
                $weeks = $this->datediffInWeeks($startDate, date('m/d/Y'));
            }
            $results[] = array(
                'title' => isset($edge->title->titleText->text) ?
                                 $edge->title->titleText->text : null,
                'id' => isset($edge->title->id) ?
                              str_replace('tt', '', $edge->title->id) : null,
                'rating' => isset($edge->title->ratingsSummary->aggregateRating) ?
                                  $edge->title->ratingsSummary->aggregateRating : null,
                'votes' => isset($edge->title->ratingsSummary->voteCount) ?
                                 $edge->title->ratingsSummary->voteCount : null,
                'LifetimeGrossAmount' => isset($edge->title->lifetimeGross->total->amount) ?
                                               $edge->title->lifetimeGross->total->amount : null,
                'LifetimeGrossCurrency' => isset($edge->title->lifetimeGross->total->currency) ?
                                                 $edge->title->lifetimeGross->total->currency : null,
                'weekendGrossAmount' => isset($edge->weekendGross->total->amount) ?
                                              $edge->weekendGross->total->amount : null,
                'weekendGrossCurrency' => isset($edge->weekendGross->total->currency) ?
                                                $edge->weekendGross->total->currency : null,
                'weeksReleased' => $weeks,
                'imgUrl' => $thumbUrl
            );
        }
        $boxOfficeResults = array(
            'weekendStartDate' => isset($data->boxOfficeWeekendChart->weekendStartDate) ?
                                        $data->boxOfficeWeekendChart->weekendStartDate : null,
            'weekendEndDate' => isset($data->boxOfficeWeekendChart->weekendEndDate) ?
                                      $data->boxOfficeWeekendChart->weekendEndDate : null,
            'titles' => $results
        );
        return $boxOfficeResults;
    }

    #========================================================[ Helper functions]===

    /**
     * Get amount of weeks between input date and current date
     * @param string $startDate like '1/2/2013' (month/day/year)
     * @param string $endDate current date! like '1/2/2013' (month/day/year)
     * @return int number of weeks
     */
    function datediffInWeeks($startDate, $endDate)
    {
        if($startDate > $endDate) return $this->datediffInWeeks($endDate, $startDate);
        $first = \DateTime::createFromFormat('m/d/Y', $startDate);
        $second = \DateTime::createFromFormat('m/d/Y', $endDate);
        return ceil($first->diff($second)->days/7);
    }

}
