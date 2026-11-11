<?php

#############################################################################
# imdbGraphQLPHP                                 ed (github user: duck7000) #
# written by Giorgos Giagas                                                 #
# written extended & maintained by ed (github user: duck7000)               #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * A title on IMDb
 * @author Georgos Giagas
 * @author Izzy (izzysoft AT qumran DOT org)
 * @author Ed
 */
class Title extends MdbBase
{

    protected $imageFunctions;
    protected $akas = array();
    protected $releaseDates = array();
    protected $countries = array();
    protected $creditsCast = array();
    protected $creditsPrincipal = array();
    protected $creditsComposer = array();
    protected $creditsStunts = array();
    protected $creditsThanks = array();
    protected $creditsVisualEffects = array();
    protected $creditsSpecialEffects = array();
    protected $creditsVoiceActor = array();
    protected $creditsDirector = array();
    protected $creditsProducer = array();
    protected $creditsWriter = array();
    protected $creditsCinematographer = array();
    protected $languages = array();
    protected $interestKeywords = array();
    protected $interests = array();
    protected $keywords = array();
    protected $mainPoster = '';
    protected $mainPosterThumb = '';
    protected $mainPlotoutline = '';
    protected $mainTitleYearMovietype = array();
    protected $mainTop250 = 0;
    protected $mainRatingVotes = array();
    protected $mainMetacritics = array();
    protected $mainRank = array();
    protected $mainPhoto = array();
    protected $trailers = array();
    protected $videos = array();
    protected $mainAwards = array();
    protected $awards = array();
    protected $genres = array();
    protected $quotes = array();
    protected $recommendations = array();
    protected $runtimes = array();
    protected $mpaas = array();
    protected $parentsGuide = array();
    protected $plot = array();
    protected $seasonEpisodes = array();
    protected $soundtracks = array();
    protected $taglines = array();
    protected $trivias = array();
    protected $isOngoing = null;
    protected $goofs = array();
    protected $crazyCredits = array();
    protected $locations = array();
    protected $compCreditsProd = array();
    protected $compCreditsDist = array();
    protected $compCreditsSpecial = array();
    protected $compCreditsOther = array();
    protected $connections = array();
    protected $externalSites = array();
    protected $productionBudget = array();
    protected $grosses = array();
    protected $alternateversions = array();
    protected $soundMix = array();
    protected $colors = array();
    protected $aspectRatio = array();
    protected $cameras = array();
    protected $featuredReviews = array();
    protected $faqs = array();
    protected $isAdult = null;
    protected $watchOption = array();
    protected $status = null;
    protected $news = array();

    /**
     * @param string $id IMDb ID. e.g. 285331 for https://www.imdb.com/title/tt0285331/
     * @param string Config $config OPTIONAL override default config
     * @param string LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param string CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(string $id, ?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->setid($id);
        $this->imageFunctions = new Image();
    }

    #-------------------------------------------------------------[ Title, originalTitle, year, endYear and movietype ]---
    /**
     * Get title, originalTitle, year, endYear and movietype data in one method
     * @return array
     */
    public function titleYearMovietype()
    {
        $query = <<<EOF
query TitleYearMovietype(\$id: ID!) {
  title(id: \$id) {
    titleText {
      text
    }
    originalTitleText {
      text
    }
    titleType {
      text
    }
    releaseYear {
      year
      endYear
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "TitleYearMovietype", ["id" => "tt$this->imdbID"]);
        $this->mainTitleYearMovietype = array(
            'title' => isset($data->title->titleText->text) ?
                             trim(str_replace('"', ':', trim($data->title->titleText->text, '"'))) : null,
            'originalTitle' => isset($data->title->originalTitleText->text) ?
                                     trim(str_replace('"', ':', trim($data->title->originalTitleText->text, '"'))) : null,
            'year' => isset($data->title->releaseYear->year) ?
                            $data->title->releaseYear->year : null,
            'endYear' => isset($data->title->releaseYear->endYear) ?
                               $data->title->releaseYear->endYear : null,
            'movietype' => isset($data->title->titleType->text) ?
                                 $data->title->titleType->text : null
        );
        return $this->mainTitleYearMovietype;
    }

    #---------------------------------------------------------------[ Runtime ]---
    /**
     * Retrieve all runtimes and their descriptions
     * @note time is the length in minutes, country optionally exists for alternate cuts, annotations is an array of comments
     * @return array
     */
    public function runtime()
    {
        if (empty($this->runtimes)) {
            $query = <<<EOF
query Runtimes(\$id: ID!) {
  title(id: \$id) {
    runtimes(first: 9999) {
      edges {
        node {
          attributes {
            text
          }
          country {
            text
          }
          seconds
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Runtimes", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->runtimes;
            }
            if (isset($data->title->runtimes->edges) &&
                is_array($data->title->runtimes->edges) &&
                count($data->title->runtimes->edges) > 0
               )
            {
                foreach ($data->title->runtimes->edges as $edge) {
                    $attributes = array();
                    if (isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                       )
                    {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                $attributes[] = $attribute->text;
                            }
                        }
                    }
                    $this->runtimes[] = array(
                        'time' => isset($edge->node->seconds) ?
                                        $edge->node->seconds / 60 : null,
                        'annotations' => $attributes,
                        'country' => isset($edge->node->country->text) ?
                                           $edge->node->country->text : null
                    );
                }
            }
        }
        return $this->runtimes;
    }

    #----------------------------------------------------------[ Rating Votes ]---
    /**
     * Get movie rating and votes
     * @return array
     */
    public function ratingVotes()
    {
        if (empty($this->mainRatingVotes)) {
            $query = <<<EOF
query RatingVotes(\$id: ID!) {
  title(id: \$id) {
    ratingsSummary {
      aggregateRating
      voteCount
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "RatingVotes", ["id" => "tt$this->imdbID"]);
            $this->mainRatingVotes = array(
                    'rating' => isset($data->title->ratingsSummary->aggregateRating) ?
                                      $data->title->ratingsSummary->aggregateRating : 0,
                    'votes' => isset($data->title->ratingsSummary->voteCount) ?
                                     $data->title->ratingsSummary->voteCount : 0
                );
        }
        return $this->mainRatingVotes;
    }

    #----------------------------------------------------------[ Metacritics ]---
    /**
     * Get metacritic data like score and reviews
     * @return array
     */
    public function metacritic()
    {
        if (empty($this->mainMetacritics)) {
            $query = <<<EOF
query Metacritic(\$id: ID!) {
  title(id: \$id) {
    metacritic {
      url
      metascore {
        score
        reviewCount
      }
      reviews(first:9999) {
        edges {
          node {
            reviewer
            score
            site
            url
            quote {
              value
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Metacritic", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title->metacritic)) {
                return $this->mainMetacritics;
            }
            $reviews = array();
            if (isset($data->title->metacritic->reviews->edges) &&
                is_array($data->title->metacritic->reviews->edges) &&
                count($data->title->metacritic->reviews->edges) > 0
               )
            {
                foreach ($data->title->metacritic->reviews->edges as $edge) {
                    $reviews[] = array(
                        'reviewer' => isset($edge->node->reviewer) ?
                                            $edge->node->reviewer : null,
                        'score' => isset($edge->node->score) ?
                                         $edge->node->score : 0,
                        'quote' => isset($edge->node->quote->value) ?
                                         $edge->node->quote->value : null,
                        'siteName' => isset($edge->node->site) ?
                                            $edge->node->site : null,
                        'siteUrl' => isset($edge->node->url) ?
                                           $edge->node->url : null
                    );
                }
            }
            $this->mainMetacritics = array(
                    'url' => isset($data->title->metacritic->url) ?
                                   $data->title->metacritic->url : null,
                    'score' => isset($data->title->metacritic->metascore->score) ?
                                     $data->title->metacritic->metascore->score : 0,
                    'reviewCount' => isset($data->title->metacritic->metascore->reviewCount) ?
                                           $data->title->metacritic->metascore->reviewCount : 0,
                    'reviews' => $reviews
                );
        }
        return $this->mainMetacritics;
    }

    #----------------------------------------------------------[ Popularity ]---
    /**
     * Get movie popularity rank
     * @return array
     */
    public function rank()
    {
        if (empty($this->mainRank)) {
            $query = <<<EOF
query Rank(\$id: ID!) {
  title(id: \$id) {
    meterRanking {
      currentRank
      rankChange {
        changeDirection
        difference
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Rank", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->meterRanking->currentRank)) {
                $this->mainRank = array(
                    'currentRank' => $data->title->meterRanking->currentRank,
                    'changeDirection' => isset($data->title->meterRanking->rankChange->changeDirection) ?
                                               $data->title->meterRanking->rankChange->changeDirection : null,
                    'difference' => isset($data->title->meterRanking->rankChange->difference) ?
                                          $data->title->meterRanking->rankChange->difference : null
                );
            }
        }
        return $this->mainRank;
    }

    #----------------------------------------------------------[ FAQ ]---
    /**
     * Get movie frequently asked questions, it includes questions with and without answer
     * @param bool $spoil include spoilers or not
     * @return array
     */
    public function faq($spoil = false)
    {
        if (empty($this->faqs)) {
            $filter = $spoil === false ? ', filter: {spoilers: EXCLUDE_SPOILERS}' : '';
            $query = <<<EOF
question {
  plainText
}
answer {
  plainText
}
isSpoiler
EOF;
            $data = $this->graphQlGetAll("Faq", "faqs", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $this->faqs[] = array(
                        'question' => isset($edge->node->question->plainText) ?
                                            $edge->node->question->plainText : null,
                        'answer' => isset($edge->node->answer->plainText) ?
                                          $edge->node->answer->plainText : null,
                        'isSpoiler' => $edge->node->isSpoiler
                    );
                }
            }
        }
        return $this->faqs;
    }

    #-------------------------------------------------------[ Recommendations ]---
    /**
     * Get recommended/more like this movies
     * @return array
     */
    public function recommendation()
    {
        if (empty($this->recommendations)) {
            $query = <<<EOF
query Recommendations(\$id: ID!) {
  title(id: \$id) {
    moreLikeThisTitles(first: 12) {
      edges {
        node {
          id
          titleText {
            text
          }
          ratingsSummary {
            aggregateRating
          }
          primaryImage {
            url
            width
            height
          }
          releaseYear {
            year
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Recommendations", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->recommendations;
            }
            if (isset($data->title->moreLikeThisTitles->edges) &&
                is_array($data->title->moreLikeThisTitles->edges) &&
                count($data->title->moreLikeThisTitles->edges) > 0
               )
            {
                foreach ($data->title->moreLikeThisTitles->edges as $edge) {
                    $thumb = null;
                    if (!empty($edge->node->primaryImage->url)) {
                        $fullImageWidth = $edge->node->primaryImage->width;
                        $fullImageHeight = $edge->node->primaryImage->height;
                        $newImageWidth = $this->config->recommendationThumbnailWidth;
                        $newImageHeight = $this->config->recommendationThumbnailHeight;
                        $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                        $thumb = $img . $parameter;
                    }
                    $this->recommendations[] = array(
                        'title' => isset($edge->node->titleText->text) ?
                                         $edge->node->titleText->text : null,
                        'imdbid' => isset($edge->node->id) ?
                                          str_replace('tt', '', $edge->node->id) : null,
                        'rating' => isset($edge->node->ratingsSummary->aggregateRating) ?
                                          $edge->node->ratingsSummary->aggregateRating : null,
                        'img' => $thumb,
                        'year' => isset($edge->node->releaseYear->year) ?
                                        $edge->node->releaseYear->year : null
                    );
                }
            }
        }
        return $this->recommendations;
    }

    #--------------------------------------------------------[ Language ]---
    /**
     * Get all spoken languages spoken in this title
     * @return array
     */
    public function language()
    {
        if (empty($this->languages)) {
            $query = <<<EOF
query Languages(\$id: ID!) {
  title(id: \$id) {
    spokenLanguages {
      spokenLanguages {
        id
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Languages", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->languages;
            }
            if (isset($data->title->spokenLanguages->spokenLanguages) &&
                is_array($data->title->spokenLanguages->spokenLanguages) &&
                count($data->title->spokenLanguages->spokenLanguages) > 0
               )
            {
                foreach ($data->title->spokenLanguages->spokenLanguages as $language) {
                    if (!empty($language->text)) {
                        $this->languages[] = array(
                            'id' => isset($language->id) ?
                                          $language->id : null,
                            'text' => $language->text
                        );
                    }
                }
            }
            return $this->languages;
        }
    }

    #--------------------------------------------------------------[ Genre ]---
    /**
     * Get all genres the movie is registered for
     * @return array
     */
    public function genre()
    {
        if (empty($this->genres)) {
            $query = <<<EOF
query Genres(\$id: ID!) {
  title(id: \$id) {
    titleGenres {
      genres {
        genre {
          text
        }
        subGenres {
          keyword {
            text {
              text
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Genres", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->genres;
            }
            if (isset($data->title->titleGenres->genres) &&
                is_array($data->title->titleGenres->genres) &&
                count($data->title->titleGenres->genres) > 0
               )
            {
                foreach ($data->title->titleGenres->genres as $edge) {
                    $subGenres = array();
                    if (isset($edge->subGenres) &&
                        is_array($edge->subGenres) &&
                        count($edge->subGenres) > 0
                       )
                    {
                        foreach ($edge->subGenres as $subGenre) {
                            if (!empty($subGenre->keyword->text->text)) {
                                $subGenres[] = ucwords($subGenre->keyword->text->text);
                            }
                        }
                    }
                    $this->genres[] = array(
                        'mainGenre' => isset($edge->genre->text) ?
                                             $edge->genre->text : null,
                        'subGenre' => $subGenres
                    );
                }
            }
        }
        return $this->genres;
    }

    #--------------------------------------------------------[ PlotOutline ]---
    /**
     * Get the plotoutline for the movie
     * @return string
     */
    public function plotoutline()
    {
        if (empty($this->mainPlotoutline)) {
            $query = <<<EOF
query PlotOutline(\$id: ID!) {
  title(id: \$id) {
    plot {
      plotText {
        plainText
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "PlotOutline", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->plot->plotText->plainText)) {
                $this->mainPlotoutline = $data->title->plot->plotText->plainText;
            }
        }
        return $this->mainPlotoutline;
    }

    #--------------------------------------------------------[ Photo ]---
    /**
     * Get the main photo image url for thumbnail or full size
     * @param bool $thumb true: thumbnail (height: 281), false: large (max 1000 pixels)
     * @return string
     */
    public function photo($thumb = true)
    {
        if (empty($this->mainPoster)) {
            $this->populatePoster();
        }
        if (!$thumb && empty($this->mainPoster)) {
            return '';
        }
        if ($thumb && empty($this->mainPosterThumb)) {
            return '';
        }
        if ($thumb) {
            return $this->mainPosterThumb;
        }
        return $this->mainPoster;
    }

    #-------------------------------------------------[ Country ]---
    /**
     * Get country of origin
     * @return array
     */
    public function country()
    {
        if (empty($this->countries)) {
            $query = <<<EOF
query Countries(\$id: ID!) {
  title(id: \$id) {
    countriesOfOrigin {
      countries {
        id
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Countries", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->countries;
            }
            if (isset($data->title->countriesOfOrigin->countries) &&
                is_array($data->title->countriesOfOrigin->countries) &&
                count($data->title->countriesOfOrigin->countries) > 0
               )
            {
                foreach ($data->title->countriesOfOrigin->countries as $country) {
                    if (!empty($country->text)) {
                        $this->countries[] = array(
                            'id' => isset($country->id) ?
                                          $country->id : null,
                            'text' => $country->text
                        );
                    }
                }
            }
        }
        return $this->countries;
    }

    #-------------------------------------------------[ Release date ]---
    /**
     * Get all release dates for this title
     * @return array
     */
    public function releaseDate()
    {
        if (empty($this->releaseDates)) {
            $query = <<<EOF
country {
  text
}
day
month
year
attributes {
  text
}
EOF;
            $data = $this->graphQlGetAll("ReleaseDates", "releaseDates", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $attributes = array();
                    if (isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                       )
                    {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                $attributes[] = $attribute->text;
                            }
                        }
                    }
                    $this->releaseDates[] = array(
                        'country' => isset($edge->node->country->text) ?
                                           $edge->node->country->text : null,
                        'day' => isset($edge->node->day) ?
                                       $edge->node->day : null,
                        'month' => isset($edge->node->month) ?
                                         $edge->node->month : null,
                        'year' => isset($edge->node->year) ?
                                        $edge->node->year : null,
                        'attributes' => $attributes
                    );
                }
            }
        }
        return $this->releaseDates;
    }

    #------------------------------------------------------------[ Movie AKA ]---
    /**
     * Get movie's alternative names
     * @note The first item in the list will be the original title
     * @note Ordered Ascending by Country
     * @return array
     */
    public function alsoknow()
    {
        if (empty($this->akas)) {
            $filter = ', sort: {order: ASC by: COUNTRY}';
            $query = <<<EOF
country {
  id
  text
}
text
attributes {
  text
}
language {
  id
  text
}
EOF;
            $data = $this->graphQlGetAll("AlsoKnow", "akas", $query, $filter);
            if (empty($mainTitleYearMovietype)) {
                $mainTitleYearMovietype = $this->titleYearMovietype();
            }
            $originalTitle = $mainTitleYearMovietype['originalTitle'];
            if (!empty($originalTitle)) {
                $this->akas[] = array(
                    'title' => $originalTitle,
                    'country' => "(Original Title)",
                    'countryId' => null,
                    'language' => null,
                    'languageId' => null,
                    'comment' => array()
                );
            }
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $comments = array();
                    if (isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                       )
                    {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                $comments[] = $attribute->text;
                            }
                        }
                    }
                    $this->akas[] = array(
                        'title' => isset($edge->node->text) ?
                                         $edge->node->text : null,
                        'country' => isset($edge->node->country->text) ?
                                           ucwords($edge->node->country->text) : 'Unknown',
                        'countryId' => isset($edge->node->country->id) ?
                                             $edge->node->country->id : null,
                        'language' => isset($edge->node->language->text) ?
                                            ucwords($edge->node->language->text) : null,
                        'languageId' => isset($edge->node->language->id) ?
                                              $edge->node->language->id : null,
                        'comment' => $comments
                    );
                }
            }
        }
        return $this->akas;
    }

    #-------------------------------------------------------[ MPAA / PG / FSK ]---
    /**
     * Get the MPAA rating / Parental Guidance / Age rating for this title by country
     * @return array
     */
    public function mpaa()
    {
        if (empty($this->mpaas)) {
            $query = <<<EOF
country {
  text
}
rating
attributes {
  text
}
EOF;
            $data = $this->graphQlGetAll("Mpaa", "certificates", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $comments = array();
                    if (isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                    )
                    {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                $comments[] = $attribute->text;
                            }
                        }
                    }
                    $this->mpaas[] = array(
                        'country' => isset($edge->node->country->text) ?
                                           $edge->node->country->text : null,
                        'rating' => isset($edge->node->rating) ?
                                          $edge->node->rating : null,
                        'comment' => $comments
                    );
                }
            }
        }
        return $this->mpaas;
    }

    #-------------------------------------------------------[ ParentsGuide ]---
    /**
     * Info for parents like Violence, Drugs. Alcohol etc
     * @param bool $spoil if true spoilers are also included
     * @return array
     *      [nudity]  Array()
     *          [severity]            (string) None (like mild,severe,none etc)
     *          [severityVotedFor]    (int) 34 (how many people voted for this severity)
     *          [totalSeverityVotes]  (int) 64 (total amount of voters)
     *          [guideItems]  Array()
     *              [0]  Array()
     *                  [isSpoiler]  (bool) (indicates if entry is a spoiler or not)
     *                  [guideText]  (string) A couple in swimwear are seen lying in a sexualised pose together
     */
    public function parentsGuide($spoil = false)
    {
        $filter = '';
        if ($spoil === false) {
            $filter = '(filter: {spoilers: EXCLUDE_SPOILERS})';
        }

        $query = <<<EOF
query ParentsGuide (\$id: ID!) {
  title(id: \$id) {
    parentsGuide {
      categories $filter {
        category {
          id
        }
        severity {
          text
          votedFor
        }
        totalSeverityVotes
        guideItems(first: 9999) {
          edges {
            node {
              isSpoiler
              text {
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
        $data = $this->graphql->query($query, "ParentsGuide", ["id" => "tt$this->imdbID"]);
        if (!isset($data->title)) {
            return $this->parentsGuide;
        }
        if (isset($data->title->parentsGuide->categories) &&
            is_array($data->title->parentsGuide->categories) &&
            count($data->title->parentsGuide->categories) > 0
           )
        {
            foreach ($data->title->parentsGuide->categories as $category) {
                $guideItems = array();
                if (isset($category->guideItems->edges) &&
                    is_array($category->guideItems->edges) &&
                    count($category->guideItems->edges) > 0
                   )
                {
                    foreach ($category->guideItems->edges as $edge) {
                        $guideItems[] = array(
                            'isSpoiler' => $edge->node->isSpoiler,
                            'guideText' => isset($edge->node->text->plainText) ?
                                                 $edge->node->text->plainText : null
                        );
                    }
                }
                $this->parentsGuide[strtolower($category->category->id)] = array(
                    'severity' => isset($category->severity->text) ?
                                        $category->severity->text : null,
                    'severityVotedFor' => isset($category->severity->votedFor) ?
                                                $category->severity->votedFor : null,
                    'totalSeverityVotes' => isset($category->totalSeverityVotes) ?
                                                  $category->totalSeverityVotes : null,
                    'guideItems' => $guideItems
                );
            }
        }
        return $this->parentsGuide;
    }

    #----------------------------------------------[ Top250 ]---
    /**
     * Find the position of a movie or tv show in the top 250
     * @return int
     */
    public function top250()
    {
        if ($this->mainTop250 == 0) {
            $query = <<<EOF
query TopRated(\$id: ID!) {
  title(id: \$id) {
    ratingsSummary {
      topRanking {
        rank
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "TopRated", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->ratingsSummary->topRanking->rank)) {
                if ($data->title->ratingsSummary->topRanking->rank <= 250) {
                    $this->mainTop250 = $data->title->ratingsSummary->topRanking->rank;
                }
            }
        }
        return $this->mainTop250;
    }

    #----------------------------------------------[ Plot ]---
    /**
     * Get movie plots
     * @param bool $spoil true: spoilers are also included, default: false.
     * @return array
     */
    public function plot($spoil = false)
    {
        if (empty($this->plot)) {
            $filter = $spoil === false ? ',filter:{spoilers:EXCLUDE_SPOILERS}' : '';
            $query = <<<EOF
query Plots(\$id: ID!) {
  title(id: \$id) {
    plots(first: 9999$filter) {
      edges {
        node {
          author
          plotText {
            plainText
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Plots", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->plot;
            }
            if (isset($data->title->plots->edges) &&
                is_array($data->title->plots->edges) &&
                count($data->title->plots->edges) > 0
               )
            {
                foreach ($data->title->plots->edges as $edge) {
                    if (!empty($edge->node->plotText->plainText)) {
                        $this->plot[] = array(
                            'plot' => $edge->node->plotText->plainText,
                            'author' => isset($edge->node->author) ?
                                              $edge->node->author : null
                        );
                    }
                }
            }
        }
        return $this->plot;
    }

    #----------------------------------------------[ Tagline ]---
    /**
     * Get all available taglines for the movie
     * @return array
     */
    public function tagline()
    {
        if (empty($this->taglines)) {
            $query = <<<EOF
query Taglines(\$id: ID!) {
  title(id: \$id) {
    taglines(first: 9999) {
      edges {
        node {
          text
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Taglines", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->taglines;
            }
            if (isset($data->title->taglines->edges) &&
                is_array($data->title->taglines->edges) &&
                count($data->title->taglines->edges) > 0
               )
            {
                foreach ($data->title->taglines->edges as $edge) {
                    if (!empty($edge->node->text)) {
                        $this->taglines[] = $edge->node->text;
                    }
                }
            }
        }
        return $this->taglines;
    }

    #----------------------------------------------------------------[ PrincipalCredits ]---
    /**
    * Get principalCredits for this title (limited to 3 items per category)
    * @return array
    */
    public function principalCredits()
    {
        if (empty($this->creditsPrincipal)) {
            $query = <<<EOF
query PrincipalCredits(\$id: ID!) {
  title(id: \$id) {
    principalCredits {
      credits(limit: 3) {
        name {
          nameText {
            text
          }
          id
        }
        category {
          text
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "PrincipalCredits", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->creditsPrincipal;
            }
            if (isset($data->title->principalCredits) &&
                is_array($data->title->principalCredits) &&
                count($data->title->principalCredits) > 0
               )
            {
                foreach ($data->title->principalCredits as $value){
                    $category = 'Unknown';
                    $credits = array();
                    if (!empty($value->credits[0]->category->text)) {
                        $category = $value->credits[0]->category->text;
                        if ($category == "Actor" || $category == "Actress") {
                            $category = "Star";
                        }
                    }
                    if (isset($value->credits) &&
                        is_array($value->credits) &&
                        count($value->credits) > 0
                       )
                    {
                        foreach ($value->credits as $credit) {
                            $credits[] = array(
                                'name' => isset($credit->name->nameText->text) ?
                                                $credit->name->nameText->text : null,
                                'imdbid' => isset($credit->name->id) ?
                                                  str_replace('nm', '', $credit->name->id) : null
                            );
                        }
                    } elseif ($category == 'Unknown') {
                            continue;
                    }
                    $this->creditsPrincipal[$category] = $credits;
                }
            }
        }
        return $this->creditsPrincipal;
    }

    #----------------------------------------------------------------[ Cast]---
    /**
     * Get cast members for this title
     * @return array
     *      [imdb]          string  0922035
     *      [name]          string  Dominic West
     *      [alias]         string  alias name
     *      [credited]      bool    false if stated (uncredited)
     *      [character]     array   Det. James 'Jimmy' McNulty
     *      [comment]       array   comments like archive voice etc
     *      [thumb]         string  https://ia.media-imdb.com/images/M/MV5BMTY5NjQwNDY2OV5BMl5BanBnXkFtZTcwMjI2ODQ1MQ@@._V1_SY44_CR0,0,32,44_AL_.jpg
     */
    public function cast()
    {
        if (empty($this->creditsCast)) {
            $filter = ', filter:{categories:["cast"]}';
            $query = <<<EOF
name {
  nameText {
    text
  }
  id
  primaryImage {
    url
    width
    height
  }
}
... on Cast {
  characters {
    name
  }
  attributes {
    text
  }
}
EOF;
            $data = $this->graphQlGetAll("CreditQuery", "credits", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $castCharacters = array();
                    if (isset($edge->node->characters) &&
                        is_array($edge->node->characters) &&
                        count($edge->node->characters) > 0
                       )
                    {
                        foreach ($edge->node->characters as $character) {
                            if (!empty($character->name)) {
                                $castCharacters[] = $character->name;
                            }
                        }
                    }
                    $comments = array();
                    $nameAlias = null;
                    $credited = true;
                    if (isset($edge->node->attributes) &&
                        is_array($edge->node->attributes) &&
                        count($edge->node->attributes) > 0
                       )
                    {
                        foreach ($edge->node->attributes as $attribute) {
                            if (!empty($attribute->text)) {
                                if (strpos($attribute->text, "as ") !== false) {
                                    $nameAlias = trim(ltrim($attribute->text, "as"));
                                } elseif (stripos($attribute->text, "uncredited") !== false) {
                                    $credited = false;
                                } else {
                                    $comments[] = $attribute->text;
                                }
                            }
                        }
                    }
                    $imgUrl = null;
                    if (!empty($edge->node->name->primaryImage->url)) {
                        $fullImageWidth = $edge->node->name->primaryImage->width;
                        $fullImageHeight = $edge->node->name->primaryImage->height;
                        $newImageWidth = $this->config->castThumbnailWidth;
                        $newImageHeight = $this->config->castThumbnailHeight;
                        $img = str_replace('.jpg', '', $edge->node->name->primaryImage->url);
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                        $imgUrl = $img . $parameter;
                    }
                    $this->creditsCast[] = array(
                        'imdb' => isset($edge->node->name->id) ?
                                        str_replace('nm', '', $edge->node->name->id) : null,
                        'name' => isset($edge->node->name->nameText->text) ?
                                        $edge->node->name->nameText->text : null,
                        'alias' => $nameAlias,
                        'credited' => $credited,
                        'character' => $castCharacters,
                        'comment' => $comments,
                        'thumb' => $imgUrl
                    );
                }
            }
            return $this->creditsCast;
        }
    }

    #-------------------------------------------------------------[ Directors ]---
    /**
     * Get the directors of this movie
     * @return array
     */
    public function director()
    {
        if (!empty($this->creditsDirector)) {
            return $this->creditsDirector;
        }
        $directorData = $this->creditHelper("director");
        return $this->creditsDirector = $directorData;
    }

    #-------------------------------------------------------------[ Cinematographers ]---
    /**
     * Get the cinematographers of this title
     * @return array
     */
    public function cinematographer()
    {
        if (!empty($this->creditsCinematographer)) {
            return $this->creditsCinematographer;
        }
        $cinematographerData = $this->creditHelper("cinematographer");
        return $this->creditsCinematographer = $cinematographerData;
    }

    #---------------------------------------------------------------[ Writers ]---
    /**
     * Get the writers of this title
     * @return array
     */
    public function writer()
    {
        if (!empty($this->creditsWriter)) {
            return $this->creditsWriter;
        }
        $writerData = $this->creditHelper("writer");
        return $this->creditsWriter = $writerData;
    }

    #-------------------------------------------------------------[ Producers ]---
    /**
     * Get the producers credits of this title
     * @return array
     */
    public function producer()
    {
        if (!empty($this->creditsProducer)) {
            return $this->creditsProducer;
        }
        $producerData = $this->creditHelper("producer");
        return $this->creditsProducer = $producerData;
    }

    #-------------------------------------------------------------[ Composers ]---
    /**
     * Get the composers ("Original Music by...") of this title
     * @return array
     */
    public function composer()
    {
        if (!empty($this->creditsComposer)) {
            return $this->creditsComposer;
        }
        $composerData = $this->creditHelper("composer");
        return $this->creditsComposer = $composerData;
    }
    
    #-------------------------------------------------------------[ Stunts ]---
    /**
     * Get the stunts credits of this title
     * @return array
     */
    public function stunts()
    {
        if (!empty($this->creditsStunts)) {
            return $this->creditsStunts;
        }
        $stuntsData = $this->creditHelper("stunts");
        return $this->creditsStunts = $stuntsData;
    }
    
    #-------------------------------------------------------------[ Thanks ]---
    /**
     * Get the thanks credits of this title
     * @return array
     */
    public function thanks()
    {
        if (!empty($this->creditsThanks)) {
            return $this->creditsThanks;
        }
        $thanksData = $this->creditHelper("thanks");
        return $this->creditsThanks = $thanksData;
    }
    
    #-------------------------------------------------------------[ Visual Effects ]---
    /**
     * Get the Visual Effects credits of this title
     * @return array
     */
    public function visualEffects()
    {
        if (!empty($this->creditsVisualEffects)) {
            return $this->creditsVisualEffects;
        }
        $visualEffectsData = $this->creditHelper("visual_effects");
        return $this->creditsVisualEffects = $visualEffectsData;
    }
    
    #-------------------------------------------------------------[ Special Effects ]---
    /**
     * Get the Special Effects credits of this title
     * @return array
     */
    public function specialEffects()
    {
        if (!empty($this->creditsSpecialEffects)) {
            return $this->creditsSpecialEffects;
        }
        $specialEffectsData = $this->creditHelper("special_effects");
        return $this->creditsSpecialEffects = $specialEffectsData;
    }

    #-------------------------------------------------------------[ voice actor credits ]---
    /**
     * Get the voice actor credits of this title
     * @return array
     */
    public function voiceActor()
    {
        if (!empty($this->creditsVoiceActor)) {
            return $this->creditsVoiceActor;
        }
        $voiceActorData = $this->creditHelper("miscellaneous", "voice actor");
        return $this->creditsVoiceActor = $voiceActorData;
    }

    #----------------------------------------------------[ CrazyCredits ]---
    /**
     * Get the Crazy Credits of this title
     * @return array
     */
    public function crazyCredit()
    {
        if (empty($this->crazyCredits)) {
            $query = <<<EOF
text {
  plainText
}
EOF;
            $data = $this->graphQlGetAll("CrazyCredits", "crazyCredits", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    if (!empty($edge->node->text->plainText)) {
                        $this->crazyCredits[] = preg_replace('/\s\s+/', ' ', $edge->node->text->plainText);
                    }
                }
            }
        }
        return $this->crazyCredits;
    }

    #--------------------------------------------------------[ Episodes ]---
    /**
     * Get the tv show episodes of this title
     * @version The array keys reflects the real season and episodenumber! Episodes can start at 0 (pilot episode)
     * @param bool $thumb true: thumbnail (cropped from center 224x126), false: large (max width 1000 pixels)
     * @param $yearbased This gives user control if returned episodes are yearbased or season based
     * @return array
     *   [1] array()        //can be seasonnumber, year or -1 (Unknown)
     *       [1] array()          //can be episodenumber or -1 (Unknown)
     *          [imdbid]        string 1495166
     *          [title]         string Pilot
     *          [airdate]       string 7 jun. 2010
     *          [airdateParts]  array()
     *              [day]    int 7
     *              [month]  int 6
     *              [year]   int 2010
     *          [plot]     string
     *          [episode]  string //can be episodenumber or -1 (Unknown)
     *          [imgUrl]   string
     */
    public function episode($thumb = true, $yearbased = 0)
    {
        if (empty($this->seasonEpisodes)) {
            // Check if season or year based
            $seasonsData = $this->seasonYearCheck($yearbased);
            if ($seasonsData === false) {
                return $this->seasonEpisodes;
            }
            if (is_array($seasonsData) && count($seasonsData) > 0) {
                foreach ($seasonsData as $seasonsDataEdge) {
                    if (empty($seasonsDataEdge->node->text)) {
                        return $this->seasonEpisodes;
                    }
                    $seasonYear = $seasonsDataEdge->node->text;
                    $filter = $this->buildFilter($seasonYear);
                    if ($seasonYear == "Unknown") { //this is intended capitol
                        $seasonYear = -1;
                    }
                    // Get all episodes
                    $episodesData = $this->graphQlGetAllEpisodes($filter);
                    $episodes = array();
                    foreach ($episodesData as $edge) {
                        $dateParts = array(
                            'day' => isset($edge->node->releaseDate->day) ?
                                           $edge->node->releaseDate->day : null,
                            'month' => isset($edge->node->releaseDate->month) ?
                                             $edge->node->releaseDate->month : null,
                            'year' => isset($edge->node->releaseDate->year) ?
                                            $edge->node->releaseDate->year : null
                        );
                        $airDate = $this->buildDateString($dateParts);
                        $epNumber = null;
                        if (isset($edge->node->series->displayableEpisodeNumber->episodeNumber->episodeNumber)) {
                            $epNumber = $edge->node->series->displayableEpisodeNumber->episodeNumber->episodeNumber;
                            // Unknown episodes get a number to keep them separate.
                            if ($epNumber == "unknown") {
                                $epNumber = -1;
                            }
                        }
                        $imgUrl = null;
                        if (!empty($edge->node->primaryImage->url)) {
                            $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                            if ($thumb == true) {
                                $fullImageWidth = $edge->node->primaryImage->width;
                                $fullImageHeight = $edge->node->primaryImage->height;
                                $newImageWidth = $this->config->episodeThumbnailWidth;
                                $newImageHeight = $this->config->episodeThumbnailHeight;
                                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                                $imgUrl = $img . $parameter;
                            } else {
                                $imgUrl = $img . 'QL100_SX1000_.jpg';
                            }
                        }
                        $episode = array(
                                'imdbid' => isset($edge->node->id) ?
                                                  str_replace('tt', '', $edge->node->id) : null,
                                'title' => isset($edge->node->titleText->text) ?
                                                 $edge->node->titleText->text : null,
                                'airdate' => $airDate,
                                'airdateParts' => $dateParts,
                                'plot' => isset($edge->node->plot->plotText->plainText) ?
                                                $edge->node->plot->plotText->plainText : null,
                                'season' => $seasonYear,
                                'episode' => $epNumber,
                                'imgUrl' => $imgUrl
                            );
                        $episodes[] = $episode;
                    }
                    $this->seasonEpisodes[$seasonYear] = $episodes;
                }
            }
        }
        return $this->seasonEpisodes;
    }

    #-----------------------------------------------------------[ IsOngoing TV Series ]---
     /**
     * Can be used to check if this is a still running tv series or ended one
     * @return bool|null false if ended, true if still running or null (not a tv series)
     */
    public function isOngoing()
    {
        $query = <<<EOF
query IsOngoing(\$id: ID!) {
  title(id: \$id) {
    episodes {
      isOngoing
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "IsOngoing", ["id" => "tt$this->imdbID"]);
        $this->isOngoing = isset($data->title->episodes->isOngoing) ?
                                 $data->title->episodes->isOngoing : null;
        return $this->isOngoing;
    }

    #-----------------------------------------------------------[ Goofs ]---
    /**
     * Get the goofs of this title
     * @param bool $spoil true: spoilers are also included
     * @return array
     */
    public function goof($spoil = false)
    {
        if (empty($this->goofs)) {
            $filter = $spoil === false ? ', filter: {spoilers: EXCLUDE_SPOILERS}' : '';
            $query = <<<EOF
category {
  id
}
displayableArticle {
  body {
    plainText
  }
}
isSpoiler
EOF;
            $data = $this->graphQlGetAll("Goofs", "goofs", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    // category Id
                    $catId = !empty($edge->node->category->id) ? $edge->node->category->id : "unknown";
                    $catIdSplit = explode('_', $catId);
                    $categoryId = '';
                    foreach ($catIdSplit as $catIdKey => $catIdItem) {
                        $categoryId .= ucwords($catIdItem);
                    }
                    $categoryId = lcfirst($categoryId);
                    $this->goofs[$categoryId][] = array(
                        'content' => isset($edge->node->displayableArticle->body->plainText) ?
                                           $edge->node->displayableArticle->body->plainText : null,
                        'isSpoiler' => $edge->node->isSpoiler
                    );
                }
            }
        }
        return $this->goofs;
    }

    #-----------------------------------------------------------[ Quotes ]---
    /**
     * Get the quotes of this title
     * @return array
     */
    public function quote()
    {
        if (empty($this->quotes)) {
            $query = <<<EOF
displayableArticle {
  body {
    plaidHtml
  }
}
EOF;
            $data = $this->graphQlGetAll("Quotes", "quotes", $query);
            if (count($data) > 0) {
                foreach ($data as $key => $edge) {
                    if (!empty($edge->node->displayableArticle->body->plaidHtml)) {
                        $quoteParts = explode("<li>", $edge->node->displayableArticle->body->plaidHtml);
                        foreach ($quoteParts as $quoteItem) {
                            if (!empty(trim(strip_tags($quoteItem)))) {
                                $this->quotes[$key][] = trim(strip_tags($quoteItem));
                            }
                        }
                    }
                }
            }
        }
        return $this->quotes;
    }

    #-----------------------------------------------------------[ Trivia ]---
    /**
     * Get the trivia of this title
     * @param bool $spoil true: spoilers are also included
     * @return array
     */
    public function trivia($spoil = false)
    {
        if (empty($this->trivias)) {
            $filter = $spoil === false ? ', filter: {spoilers: EXCLUDE_SPOILERS}' : '';
            $query = <<<EOF
category {
  id
}
displayableArticle {
  body {
    plainText
  }
}
isSpoiler
trademark {
  plainText
}
relatedNames {
  nameText {
    text
  }
  id
}
EOF;
            $data = $this->graphQlGetAll("Trivia", "trivia", $query, $filter);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $names = array();
                    if (isset($edge->node->relatedNames) &&
                        is_array($edge->node->relatedNames) &&
                        count($edge->node->relatedNames) > 0
                       )
                    {
                        foreach ($edge->node->relatedNames as $name) {
                            $names[] = array(
                                'name' => isset($name->nameText->text) ?
                                                $name->nameText->text : null,
                                'id' => isset($name->id) ?
                                              str_replace('nm', '', $name->id) : null
                            );
                        }
                    }
                    // category Id
                    $catId = !empty($edge->node->category->id) ? $edge->node->category->id : "unknown";
                    $catIdSplit = explode('-', $catId);
                    $categoryId = '';
                    foreach ($catIdSplit as $catIdKey => $catIdItem) {
                        $categoryId .= ucwords($catIdItem);
                    }
                    $categoryId = lcfirst($categoryId);
                    $this->trivias[$categoryId][] = array(
                        'content' => isset($edge->node->displayableArticle->body->plainText) ?
                                           preg_replace('/\s\s+/', ' ', $edge->node->displayableArticle->body->plainText) : null,
                        'names' => $names,
                        'trademark' => isset($edge->node->trademark->plainText) ?
                                             $edge->node->trademark->plainText : null,
                        'isSpoiler' => $edge->node->isSpoiler
                    );
                }
            }
        }
        return $this->trivias;
    }

    #-----------------------------------------------------------[ Soundtrack ]---
    /**
     * Get the soundtracks listing of this title
     * @note [credits]: all credit lines as text, each index one creditline
     * @note [creditSplit]: credits split by type, name, nameId and attribute
     * @note [comment]: if not a credited person, it is considered comment as plain text
     * @return array
     *      [0] => Array()
     *          [soundtrack]  string Dangerous
     *          [credits]  Array()
     *              [0]  string Performed by The Doobie Brothers
     *              [1]  string Written by Patrick Simmons (as Pat Simmons)
     *              [2]  string Published by Soquel Songs and ASCAP
     *              [3]  string Courtesy of Capitol Records
     *          [creditSplit]  Array()
     *              [creditors]  Array()
     *                  [0]  Array()
     *                      [creditType]  string Written by
     *                      [name]        string Patrick Simmons
     *                      [nameId]      string 2003944
     *                      [attribute]   string as Pat Simmons
     *              [comment]  Array()
     *                  [0]  string Courtesy of Capitol Records
     */
    public function soundtrack()
    {
        if (empty($this->soundtracks)) {
            $query = <<<EOF
text
comments {
  plaidHtml
}
EOF;
            $data = $this->graphQlGetAll("Soundtrack", "soundtrack", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $title = 'Unknown';
                    if (!empty($edge->node->text)) {
                        $title = trim($edge->node->text);
                    }
                    $credits = array();
                    $creditComments = array();
                    $crediters = array();
                    if (isset($edge->node->comments) &&
                        is_array($edge->node->comments) &&
                        count($edge->node->comments) > 0
                       )
                    {
                        foreach ($edge->node->comments as $comment) {
                            if (!empty(trim(strip_tags($comment->plaidHtml)))) {
                                $comment = $comment->plaidHtml;
                            } else {
                                continue;
                            }
                            if (stripos($comment, "arrangement with") === false) {
                                // check and replace :
                                if (($posArrangement = stripos($comment, ":")) !== false) {
                                    $comment = substr_replace($comment, " by", $posArrangement, strlen(":"));
                                }
                                if (($posBy = stripos($comment, "by")) !== false) {
                                    // split at "by"
                                    $creditRaw = substr($comment, $posBy + 2);
                                    $creditType = trim(substr($comment, 0, $posBy + 2), "[] ");
                                    // replace characters (& and) with , and explode it in array
                                    $patterns = array(
                                        '/^.*?\K&amp;(?![^>]*\/\s*a\s*>)/',
                                        '/^.*?\Kand(?![^>]*\/\s*a\s*>)/'
                                    );
                                    $creditRaw = preg_replace($patterns, ',', $creditRaw);
                                    $creditRawParts = explode(",", $creditRaw);
                                    $creditRawParts = array_values(array_filter($creditRawParts));
                                    // loop $creditRawParts array 
                                    foreach ($creditRawParts as $value) {
                                        // check if there is any text after the anchor tag
                                        $attribute = '';
                                        if (($posAttribute = strripos($value, ">")) !== false) {
                                            $valueExtention = trim(substr($value, $posAttribute + 1), ' ()[]"');
                                            if (!empty($valueExtention)) {
                                                $attribute = $valueExtention;
                                            }
                                        }
                                        // get anchor links
                                        libxml_use_internal_errors(true);
                                        $doc = new \DOMDocument();
                                        $doc->loadHTML('<?xml encoding="UTF-8">' . $value);
                                        $anchors = $doc->getElementsByTagName('a');
                                        // check if $anchors contains any <a> records
                                        if ($anchors != null && $anchors->length > 0) {
                                            $href = $anchors->item(0)->attributes->getNamedItem('href')->nodeValue;
                                            $nameId = preg_replace('/[^0-9]+/', '', $href);
                                            $name = trim($anchors->item(0)->nodeValue);
                                        } else {
                                            // no anchor, text only, check if id is present in text form
                                            $nameId = '';
                                            $name = trim($value, "[] ");
                                            if (preg_match('/(nm?\d+)/', $value, $match)) {
                                                $nameId = preg_replace('/[^0-9]+/', '', $match[0]);
                                                $name = '';
                                            }
                                        }
                                        $crediters[] = array(
                                            'creditType' => $creditType,
                                            'name' => $name,
                                            'nameId' => $nameId,
                                            'attribute' => $attribute
                                        );
                                    }
                                } else {
                                    // no by, treat as comment in plain text
                                    $creditComments[] = trim(strip_tags($comment));
                                }
                            } else {
                                // no arrangement with, treat as comment in plain text
                                $creditComments[] = trim(strip_tags($comment));
                            }
                            // add data to $credits as plain text
                            $credits[] = trim(strip_tags($comment));
                        }
                    }
                    $this->soundtracks[] = array(
                        'soundtrack' => $title,
                        'credits' => $credits,
                        'creditSplit' => array('creditors' => $crediters, 'comment' => $creditComments)
                    );
                }
            }
        }
        return $this->soundtracks;
    }

    #-----------------------------------------------------------[ Location ]---
    /**
     * Filming locations of this title
     * @note real: Real filming location, movie: location in the movie
     * @return array
     */
    public function location()
    {
        if (empty($this->locations)) {
            $query = <<<EOF
displayableProperty {
  qualifiersInMarkdownList {
    plainText
  }
  value {
    plainText
  }
}
EOF;
            $data = $this->graphQlGetAll("FilmingLocations", "filmingLocations", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $movie = array();
                    if (isset($edge->node->displayableProperty->qualifiersInMarkdownList) &&
                        is_array($edge->node->displayableProperty->qualifiersInMarkdownList) &&
                        count($edge->node->displayableProperty->qualifiersInMarkdownList) > 0
                       )
                    {
                        foreach ($edge->node->displayableProperty->qualifiersInMarkdownList as $attribute) {
                            if (!empty($attribute->plainText)) {
                                $movie[] = $attribute->plainText;
                            }
                        }
                    }
                    $this->locations[] = array(
                        'real' => isset($edge->node->displayableProperty->value->plainText) ?
                                        $edge->node->displayableProperty->value->plainText : null,
                        'movie' => $movie
                    );
                    
                }
            }
        }
        return $this->locations;
    }

    #---------------------------------------------------[ Producing Companies ]---
    /**
     * Get Production Companies
     * @return array
     */
    public function prodCompany()
    {
        if (empty($this->compCreditsProd)) {
            $this->compCreditsProd = $this->companyCredits("production");
        }
        return $this->compCreditsProd;
    }

    #------------------------------------------------[ Distributing Companies ]---
    /**
     * Get distributors
     * @return array
     */
    public function distCompany()
    {
        if (empty($this->compCreditsDist)) {
            $this->compCreditsDist = $this->companyCredits("distribution");
        }
        return $this->compCreditsDist;
    }

    #---------------------------------------------[ Special Effects Companies ]---
    /**
     * Get Special Effects companies
     * @return array
     */
    public function specialCompany()
    {
        if (empty($this->compCreditsSpecial)) {
            $this->compCreditsSpecial = $this->companyCredits("specialEffects");
        }
        return $this->compCreditsSpecial;
    }

    #-------------------------------------------------------[ Other Companies ]---
    /**
     * Get  other companies
     * @return array
     */
    public function otherCompany()
    {
        if (empty($this->compCreditsOther)) {
            $this->compCreditsOther = $this->companyCredits("miscellaneous");
        }
        return $this->compCreditsOther;
    }

    #-------------------------------------------------------[ Connections ]---
    /**
     * Get connections or references with other titles
     * @return array
     */
    public function connection()
    {
        if (empty($this->connections)) {
            $query = <<<EOF
associatedTitle {
  id
  titleText {
    text
  }
  titleType {
    text
  }
  releaseYear {
    year
    endYear
  }
  series {
    series {
      titleText {
        text
      }
    }
  }
}
category {
  id
}
description {
  plainText
}
EOF;
            $edges = $this->graphQlGetAll("Connections", "connections", $query);
            if (count($edges) > 0) {
                foreach ($edges as $edge) {
                    // category Id
                    $catId = !empty($edge->node->category->id) ? $edge->node->category->id : "unknown";
                    $catIdSplit = explode('_', $catId);
                    $categoryId = '';
                    foreach ($catIdSplit as $catIdKey => $catIdItem) {
                        $categoryId .= ucwords($catIdItem);
                    }
                    $categoryId = lcfirst($categoryId);
                    $this->connections[$categoryId][] = array(
                        'titleId' => isset($edge->node->associatedTitle->id) ?
                                           str_replace('tt', '', $edge->node->associatedTitle->id) : null,
                        'titleName' => isset($edge->node->associatedTitle->titleText->text) ?
                                             $edge->node->associatedTitle->titleText->text : null,
                        'titleType' => isset($edge->node->associatedTitle->titleType->text) ?
                                             $edge->node->associatedTitle->titleType->text : null,
                        'year' => isset($edge->node->associatedTitle->releaseYear->year) ?
                                        $edge->node->associatedTitle->releaseYear->year : null,
                        'endYear' => isset($edge->node->associatedTitle->releaseYear->endYear) ?
                                           $edge->node->associatedTitle->releaseYear->endYear : null,
                        'seriesName' => isset($edge->node->associatedTitle->series->series->titleText->text) ?
                                              $edge->node->associatedTitle->series->series->titleText->text : null,
                        'description' => isset($edge->node->description->plainText) ?
                                               $edge->node->description->plainText : null
                    );
                }
            }
        }
        return $this->connections;
    }

    #-------------------------------------------------------[ External sites ]---
    /**
     * Get external website urls with info of this title, excluding external reviews.
     * @return array
     */
    public function extSites()
    {
        if (empty($this->externalSites)) {
            $query = <<<EOF
label
url
externalLinkCategory {
  id
}
externalLinkLanguages {
  text
}
EOF;
            $filter = ' filter: {excludeCategories: "review"}';
            $edges = $this->graphQlGetAll("ExternalSites", "externalLinks", $query, $filter);
            if (count($edges) > 0) {
                foreach ($edges as $edge) {
                    $language = array();
                    if (isset($edge->node->externalLinkLanguages) &&
                        is_array($edge->node->externalLinkLanguages) &&
                        count($edge->node->externalLinkLanguages) > 0
                       )
                    {
                        foreach ($edge->node->externalLinkLanguages as $lang) {
                            if (!empty($lang->text)) {
                                $language[] = $lang->text;
                            }
                        }
                    }
                    $this->externalSites[$edge->node->externalLinkCategory->id][] = array(
                        'label' => isset($edge->node->label) ?
                                         $edge->node->label : null,
                        'url' => isset($edge->node->url) ?
                                       $edge->node->url : null,
                        'language' => $language
                    );
                }
            }
        }
        return $this->externalSites;
    }

    #-------------------------------------------------------[ productionBudget ]---
    /**
     * Get productionBudget of this title
     * @return array
     */
    public function budget()
    {
        if (empty($this->productionBudget)) {
            $query = <<<EOF
query ProductionBudget(\$id: ID!) {
  title(id: \$id) {
    productionBudget {
      budget {
        amount
        currency
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "ProductionBudget", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->productionBudget;
            }
            if (!empty($data->title->productionBudget->budget->amount)) {
                $this->productionBudget = array(
                    'amount' => $data->title->productionBudget->budget->amount,
                    'currency' => isset($data->title->productionBudget->budget->currency) ?
                                        $data->title->productionBudget->budget->currency : null
                );
            }
        }
        return $this->productionBudget;
    }

    #-------------------------------------------------------[ ranked Lifetime Grosses ]---
    /**
     * Get Grosses, ranked by amount of this title
     * @return array
     */
    public function gross()
    {
        if (empty($this->grosses)) {
            $query = <<<EOF
query RankedLifetimeGrosses(\$id: ID!) {
  title(id: \$id) {
    rankedLifetimeGrosses(first: 9999) {
      edges {
        node {
          boxOfficeAreaType {
            text
          }
          total {
            amount
            currency
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "RankedLifetimeGrosses", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->grosses;
            }
            if (isset($data->title->rankedLifetimeGrosses->edges) &&
                is_array($data->title->rankedLifetimeGrosses->edges) &&
                count($data->title->rankedLifetimeGrosses->edges) > 0
               )
            {
                foreach ($data->title->rankedLifetimeGrosses->edges as $edge) {
                    if (!empty($edge->node->boxOfficeAreaType->text)) {
                        $this->grosses[] = array(
                            'areatype' => $edge->node->boxOfficeAreaType->text,
                            'amount' => isset($edge->node->total->amount) ?
                                              $edge->node->total->amount : null,
                            'currency' => isset($edge->node->total->currency) ?
                                                $edge->node->total->currency : null
                        );
                    }
                }
            }
        }
        return $this->grosses;
    }

    #-------------------------------------------------------[ Interests ]---
    /**
     * Get all interests of this title
     * @note Interests are real and checked to be accurate
     * @return array
     */
    public function interests()
    {
        if (empty($this->interests)) {
            $keywords = array (
                array('keyword' => 'Action', 'displayName' => 'Action', 'interestId' => 'in0000001'),
                array('keyword' => 'epic action', 'displayName' => 'Action Epic', 'interestId' => 'in0000002'),
                array('keyword' => 'for grown ups animation', 'displayName' => 'Adult Animation', 'interestId' => 'in0000025'),
                array('keyword' => 'Adventure', 'displayName' => 'Adventure', 'interestId' => 'in0000012'),
                array('keyword' => 'epic adventure', 'displayName' => 'Adventure Epic', 'interestId' => 'in0000015'),
                array('keyword' => 'alien invasion sci fi', 'displayName' => 'Alien Invasion', 'interestId' => 'in0000157'),
                array('keyword' => 'animals family', 'displayName' => 'Animal Adventure', 'interestId' => 'in0000092'),
                array('keyword' => 'Animation', 'displayName' => 'Animation', 'interestId' => 'in0000026'),
                array('keyword' => 'anime animation', 'displayName' => 'Anime', 'interestId' => 'in0000027'),
                array('keyword' => 'artificial intelligence sci fi', 'displayName' => 'Artificial Intelligence', 'interestId' => 'in0000158'),
                array('keyword' => 'b action', 'displayName' => 'B-Action', 'interestId' => 'in0000003'),
                array('keyword' => 'baseball sport', 'displayName' => 'Baseball', 'interestId' => 'in0000167'),
                array('keyword' => 'basketball sport', 'displayName' => 'Basketball', 'interestId' => 'in0000168'),
                array('keyword' => 'beauty game show', 'displayName' => 'Beauty Competition', 'interestId' => 'in0000102'),
                array('keyword' => 'beauty makeover reality tv', 'displayName' => 'Beauty Makeover', 'interestId' => 'in0000123'),
                array('keyword' => 'b horror', 'displayName' => 'B-Horror', 'interestId' => 'in0000108'),
                array('keyword' => 'Biography', 'displayName' => 'Biography', 'interestId' => 'in0000072'),
                array('keyword' => 'body horror', 'displayName' => 'Body Horror', 'interestId' => 'in0000109'),
                array('keyword' => 'body swap comedy', 'displayName' => 'Body Swap Comedy', 'interestId' => 'in0000031'),
                array('keyword' => 'boxing sport', 'displayName' => 'Boxing', 'interestId' => 'in0000169'),
                array('keyword' => 'buddy comedy', 'displayName' => 'Buddy Comedy', 'interestId' => 'in0000032'),
                array('keyword' => 'buddy cop comedy', 'displayName' => 'Buddy Cop', 'interestId' => 'in0000033'),
                array('keyword' => 'bumbling detective mystery', 'displayName' => 'Bumbling Detective', 'interestId' => 'in0000136'),
                array('keyword' => 'business and occupation reality tv', 'displayName' => 'Business Reality TV', 'interestId' => 'in0000142'),
                array('keyword' => 'caper crime', 'displayName' => 'Caper', 'interestId' => 'in0000050'),
                array('keyword' => 'car action', 'displayName' => 'Car Action', 'interestId' => 'in0000004'),
                array('keyword' => 'classic musical', 'displayName' => 'Classic Musical', 'interestId' => 'in0000131'),
                array('keyword' => 'classical western', 'displayName' => 'Classical Western', 'interestId' => 'in0000187'),
                array('keyword' => 'Comedy', 'displayName' => 'Comedy', 'interestId' => 'in0000034'),
                array('keyword' => 'coming of age drama', 'displayName' => 'Coming-of-Age', 'interestId' => 'in0000073'),
                array('keyword' => 'cg animation', 'displayName' => 'Computer Animation', 'interestId' => 'in0000028'),
                array('keyword' => 'concert music', 'displayName' => 'Concert', 'interestId' => 'in0000129'),
                array('keyword' => 'conspiracy thriller', 'displayName' => 'Conspiracy Thriller', 'interestId' => 'in0000176'),
                array('keyword' => 'contemporary western', 'displayName' => 'Contemporary Western', 'interestId' => 'in0000188'),
                array('keyword' => 'cooking reality tv', 'displayName' => 'Cooking & Food', 'interestId' => 'in0000124'),
                array('keyword' => 'cookery game show', 'displayName' => 'Cooking Competition', 'interestId' => 'in0000103'),
                array('keyword' => 'cop drama', 'displayName' => 'Cop Drama', 'interestId' => 'in0000051'),
                array('keyword' => 'costume drama', 'displayName' => 'Costume Drama', 'interestId' => 'in0000074'),
                array('keyword' => 'cozy mystery', 'displayName' => 'Cozy Mystery', 'interestId' => 'in0000137'),
                array('keyword' => 'Crime', 'displayName' => 'Crime', 'interestId' => 'in0000052'),
                array('keyword' => 'crime documentary', 'displayName' => 'Crime Documentary', 'interestId' => 'in0000059'),
                array('keyword' => 'crime reality tv', 'displayName' => 'Crime Reality TV', 'interestId' => 'in0000143'),
                array('keyword' => 'cyber thriller', 'displayName' => 'Cyber Thriller', 'interestId' => 'in0000177'),
                array('keyword' => 'cyberpunk sci fi', 'displayName' => 'Cyberpunk', 'interestId' => 'in0000159'),
                array('keyword' => 'dark comedy', 'displayName' => 'Dark Comedy', 'interestId' => 'in0000035'),
                array('keyword' => 'dark fantasy', 'displayName' => 'Dark Fantasy', 'interestId' => 'in0000095'),
                array('keyword' => 'dark romance', 'displayName' => 'Dark Romance', 'interestId' => 'in0000149'),
                array('keyword' => 'dating and romance reality tv', 'displayName' => 'Dating Reality TV', 'interestId' => 'in0000144'),
                array('keyword' => 'desert adventure', 'displayName' => 'Desert Adventure', 'interestId' => 'in0000013'),
                array('keyword' => 'dinosaur adventure', 'displayName' => 'Dinosaur Adventure', 'interestId' => 'in0000014'),
                array('keyword' => 'disaster action', 'displayName' => 'Disaster', 'interestId' => 'in0000005'),
                array('keyword' => 'docudrama', 'displayName' => 'Docudrama', 'interestId' => 'in0000075'),
                array('keyword' => 'Documentary', 'displayName' => 'Documentary', 'interestId' => 'in0000060'),
                array('keyword' => 'docuseries documentary', 'displayName' => 'Docuseries', 'interestId' => 'in0000061'),
                array('keyword' => 'docusoap reality tv', 'displayName' => 'Docusoap Reality TV', 'interestId' => 'in0000145'),
                array('keyword' => 'Drama', 'displayName' => 'Drama', 'interestId' => 'in0000076'),
                array('keyword' => 'drug crime', 'displayName' => 'Drug Crime', 'interestId' => 'in0000053'),
                array('keyword' => 'dystopian sci fi', 'displayName' => 'Dystopian Sci-Fi', 'interestId' => 'in0000160'),
                array('keyword' => 'epic drama', 'displayName' => 'Epic', 'interestId' => 'in0000077'),
                array('keyword' => 'erotic thriller', 'displayName' => 'Erotic Thriller', 'interestId' => 'in0000178'),
                array('keyword' => 'extreme sport', 'displayName' => 'Extreme Sport', 'interestId' => 'in0000170'),
                array('keyword' => 'fairy tale fantasy', 'displayName' => 'Fairy Tale', 'interestId' => 'in0000097'),
                array('keyword' => 'religion and spirituality documentary', 'displayName' => 'Faith & Spirit Documentary', 'interestId' => 'in0000062'),
                array('keyword' => 'Family', 'displayName' => 'Family', 'interestId' => 'in0000093'),
                array('keyword' => 'Fantasy', 'displayName' => 'Fantasy', 'interestId' => 'in0000098'),
                array('keyword' => 'epic fantasy', 'displayName' => 'Fantasy Epic', 'interestId' => 'in0000096'),
                array('keyword' => 'farce comedy', 'displayName' => 'Farce', 'interestId' => 'in0000036'),
                array('keyword' => 'feel good romance', 'displayName' => 'Feel-Good Romance', 'interestId' => 'in0000151'),
                array('keyword' => 'Film Noir', 'displayName' => 'Film Noir', 'interestId' => 'in0000054'),
                array('keyword' => 'financial drama', 'displayName' => 'Financial Drama', 'interestId' => 'in0000078'),
                array('keyword' => 'folk horror', 'displayName' => 'Folk Horror', 'interestId' => 'in0000110'),
                array('keyword' => 'food and drink documentary', 'displayName' => 'Food Documentary', 'interestId' => 'in0000063'),
                array('keyword' => 'american football sport', 'displayName' => 'Football', 'interestId' => 'in0000171'),
                array('keyword' => 'found footage horror', 'displayName' => 'Found Footage Horror', 'interestId' => 'in0000111'),
                array('keyword' => 'Game Show', 'displayName' => 'Game Show', 'interestId' => 'in0000105'),
                array('keyword' => 'gangster crime', 'displayName' => 'Gangster', 'interestId' => 'in0000055'),
                array('keyword' => 'giallo thriller', 'displayName' => 'Giallo', 'interestId' => 'in0000179'),
                array('keyword' => 'globetrotting adventure', 'displayName' => 'Globetrotting Adventure', 'interestId' => 'in0000016'),
                array('keyword' => 'gun fu action', 'displayName' => 'Gun Fu', 'interestId' => 'in0000197'),
                array('keyword' => 'hand drawn animation', 'displayName' => 'Hand-Drawn Animation', 'interestId' => 'in0000029'),
                array('keyword' => 'hardboiled mystery', 'displayName' => 'Hard-boiled Detective', 'interestId' => 'in0000138'),
                array('keyword' => 'heist crime', 'displayName' => 'Heist', 'interestId' => 'in0000056'),
                array('keyword' => 'hidden camera reality tv', 'displayName' => 'Hidden Camera', 'interestId' => 'in0000146'),
                array('keyword' => 'high concept comedy', 'displayName' => 'High-Concept Comedy', 'interestId' => 'in0000037'),
                array('keyword' => 'epic history', 'displayName' => 'Historical Epic', 'interestId' => 'in0000079'),
                array('keyword' => 'History', 'displayName' => 'History', 'interestId' => 'in0000080'),
                array('keyword' => 'history documentary', 'displayName' => 'History Documentary', 'interestId' => 'in0000064'),
                array('keyword' => 'holiday season', 'displayName' => 'Holiday', 'interestId' => 'in0000192'),
                array('keyword' => 'holiday animation', 'displayName' => 'Holiday Animation', 'interestId' => 'in0000193'),
                array('keyword' => 'holiday comedy', 'displayName' => 'Holiday Comedy', 'interestId' => 'in0000194'),
                array('keyword' => 'holiday family', 'displayName' => 'Holiday Family', 'interestId' => 'in0000195'),
                array('keyword' => 'holiday romance', 'displayName' => 'Holiday Romance', 'interestId' => 'in0000196'),
                array('keyword' => 'home makeover reality tv', 'displayName' => 'Home Improvement', 'interestId' => 'in0000125'),
                array('keyword' => 'Horror', 'displayName' => 'Horror', 'interestId' => 'in0000112'),
                array('keyword' => 'isekai anime animation', 'displayName' => 'Isekai', 'interestId' => 'in0000201'),
                array('keyword' => 'iyashikei anime animation', 'displayName' => 'Iyashikei', 'interestId' => 'in0000202'),
                array('keyword' => 'statham action', 'displayName' => 'Jason Statham Action', 'interestId' => 'in0000315'),
                array('keyword' => 'josei anime animation', 'displayName' => 'Josei', 'interestId' => 'in0000203'),
                array('keyword' => 'jukebox musical', 'displayName' => 'Jukebox Musical', 'interestId' => 'in0000132'),
                array('keyword' => 'jungle adventure', 'displayName' => 'Jungle Adventure', 'interestId' => 'in0000017'),
                array('keyword' => 'giant monster sci fi', 'displayName' => 'Kaiju', 'interestId' => 'in0000161'),
                array('keyword' => 'k drama', 'displayName' => 'Korean Drama', 'interestId' => 'in0000209'),
                array('keyword' => 'kung fu action', 'displayName' => 'Kung Fu', 'interestId' => 'in0000198'),
                array('keyword' => 'legal drama', 'displayName' => 'Legal Drama', 'interestId' => 'in0000081'),
                array('keyword' => 'legal thriller', 'displayName' => 'Legal Thriller', 'interestId' => 'in0000180'),
                array('keyword' => 'lifestyle reality tv', 'displayName' => 'Lifestyle', 'interestId' => 'in0000126'),
                array('keyword' => 'martial arts action', 'displayName' => 'Martial Arts', 'interestId' => 'in0000006'),
                array('keyword' => 'mecha anime animation', 'displayName' => 'Mecha', 'interestId' => 'in0000204'),
                array('keyword' => 'medical drama', 'displayName' => 'Medical Drama', 'interestId' => 'in0000082'),
                array('keyword' => 'military and war documentary', 'displayName' => 'Military Documentary', 'interestId' => 'in0000065'),
                array('keyword' => 'mockumentary comedy', 'displayName' => 'Mockumentary', 'interestId' => 'in0000038'),
                array('keyword' => 'monster horror', 'displayName' => 'Monster Horror', 'interestId' => 'in0000113'),
                array('keyword' => 'motorsports sport', 'displayName' => 'Motorsport', 'interestId' => 'in0000172'),
                array('keyword' => 'mountain adventure', 'displayName' => 'Mountain Adventure', 'interestId' => 'in0000018'),
                array('keyword' => 'Music', 'displayName' => 'Music', 'interestId' => 'in0000130'),
                array('keyword' => 'music documentary', 'displayName' => 'Music Documentary', 'interestId' => 'in0000066'),
                array('keyword' => 'Musical', 'displayName' => 'Musical', 'interestId' => 'in0000133'),
                array('keyword' => 'Mystery', 'displayName' => 'Mystery', 'interestId' => 'in0000139'),
                array('keyword' => 'nature documentary', 'displayName' => 'Nature Documentary', 'interestId' => 'in0000067'),
                array('keyword' => 'News', 'displayName' => 'News', 'interestId' => 'in0000211'),
                array('keyword' => 'one person army action', 'displayName' => 'One-Person-Army', 'interestId' => 'in0000007'),
                array('keyword' => 'supernatural reality tv', 'displayName' => 'Paranormal Reality TV', 'interestId' => 'in0000147'),
                array('keyword' => 'parody comedy', 'displayName' => 'Parody', 'interestId' => 'in0000039'),
                array('keyword' => 'period drama', 'displayName' => 'Period Drama', 'interestId' => 'in0000083'),
                array('keyword' => 'police procedural crime', 'displayName' => 'Police Procedural', 'interestId' => 'in0000057'),
                array('keyword' => 'politics documentary', 'displayName' => 'Political Documentary', 'interestId' => 'in0000068'),
                array('keyword' => 'political drama', 'displayName' => 'Political Drama', 'interestId' => 'in0000084'),
                array('keyword' => 'political thriller', 'displayName' => 'Political Thriller', 'interestId' => 'in0000181'),
                array('keyword' => 'pop musical', 'displayName' => 'Pop Musical', 'interestId' => 'in0000134'),
                array('keyword' => 'prison drama', 'displayName' => 'Prison Drama', 'interestId' => 'in0000085'),
                array('keyword' => 'psychological drama', 'displayName' => 'Psychological Drama', 'interestId' => 'in0000086'),
                array('keyword' => 'psychological horror', 'displayName' => 'Pyschological Horror', 'interestId' => 'in0000114'),
                array('keyword' => 'psychological thriller', 'displayName' => 'Psychological Thriller', 'interestId' => 'in0000182'),
                array('keyword' => 'quest adventure', 'displayName' => 'Quest', 'interestId' => 'in0000019'),
                array('keyword' => 'quirky comedy', 'displayName' => 'Quirky Comedy', 'interestId' => 'in0000040'),
                array('keyword' => 'quiz game show', 'displayName' => 'Quiz Show', 'interestId' => 'in0000104'),
                array('keyword' => 'raunchy comedy', 'displayName' => 'Raunchy Comedy', 'interestId' => 'in0000041'),
                array('keyword' => 'Reality TV', 'displayName' => 'Reality TV', 'interestId' => 'in0000148'),
                array('keyword' => 'road trip adventure', 'displayName' => 'Road Trip', 'interestId' => 'in0000020'),
                array('keyword' => 'rock musical', 'displayName' => 'Rock Musical', 'interestId' => 'in0000135'),
                array('keyword' => 'Romance', 'displayName' => 'Romance', 'interestId' => 'in0000152'),
                array('keyword' => 'romantic comedy', 'displayName' => 'Romantic Comedy', 'interestId' => 'in0000153'),
                array('keyword' => 'epic romance', 'displayName' => 'Romantic Epic', 'interestId' => 'in0000150'),
                array('keyword' => 'samurai action', 'displayName' => 'Samurai', 'interestId' => 'in0000199'),
                array('keyword' => 'satire comedy', 'displayName' => 'Satire', 'interestId' => 'in0000042'),
                array('keyword' => 'science and technology documentary', 'displayName' => 'Science & Technology Documentary', 'interestId' => 'in0000069'),
                array('keyword' => 'Sci Fi', 'displayName' => 'Sci-Fi', 'interestId' => 'in0000162'),
                array('keyword' => 'epic sci fi', 'displayName' => 'Sci-Fi Epic', 'interestId' => 'in0000163'),
                array('keyword' => 'screwball comedy', 'displayName' => 'Screwball Comedy', 'interestId' => 'in0000043'),
                array('keyword' => 'sea adventure', 'displayName' => 'Sea Adventure', 'interestId' => 'in0000021'),
                array('keyword' => 'seinen anime animation', 'displayName' => 'Seinen', 'interestId' => 'in0000205'),
                array('keyword' => 'serial killer crime', 'displayName' => 'Serial Killer', 'interestId' => 'in0000183'),
                array('keyword' => 'shojo anime animation', 'displayName' => 'Shōjo', 'interestId' => 'in0000206'),
                array('keyword' => 'shonen anime animation', 'displayName' => 'Shōnen', 'interestId' => 'in0000207'),
                array('keyword' => 'Short', 'displayName' => 'Short', 'interestId' => 'in0000212'),
                array('keyword' => 'showbiz drama', 'displayName' => 'Showbiz Drama', 'interestId' => 'in0000087'),
                array('keyword' => 'sitcom comedy', 'displayName' => 'Sitcom', 'interestId' => 'in0000044'),
                array('keyword' => 'sketch comedy', 'displayName' => 'Sketch Comedy', 'interestId' => 'in0000045'),
                array('keyword' => 'slapstick comedy', 'displayName' => 'Slapstick', 'interestId' => 'in0000046'),
                array('keyword' => 'slasher horror', 'displayName' => 'Slasher Horror', 'interestId' => 'in0000115'),
                array('keyword' => 'slice of life anime animation', 'displayName' => 'Slice of Life', 'interestId' => 'in0000208'),
                array('keyword' => 'soap opera drama', 'displayName' => 'Soap Opera', 'interestId' => 'in0000088'),
                array('keyword' => 'soccer sport', 'displayName' => 'Soccer', 'interestId' => 'in0000173'),
                array('keyword' => 'space sci fi', 'displayName' => 'Space Sci-Fi', 'interestId' => 'in0000164'),
                array('keyword' => 'spaghetti western', 'displayName' => 'Spaghetti Western', 'interestId' => 'in0000190'),
                array('keyword' => 'splatter horror', 'displayName' => 'Splatter Horror', 'interestId' => 'in0000116'),
                array('keyword' => 'Sport', 'displayName' => 'Sport', 'interestId' => 'in0000174'),
                array('keyword' => 'sports documentary', 'displayName' => 'Sports Documentary', 'interestId' => 'in0000070'),
                array('keyword' => 'spy thriller', 'displayName' => 'Spy', 'interestId' => 'in0000184'),
                array('keyword' => 'stand up comedy', 'displayName' => 'Stand-Up', 'interestId' => 'in0000047'),
                array('keyword' => 'steampunk sci fi', 'displayName' => 'Steampunk', 'interestId' => 'in0000165'),
                array('keyword' => 'steamy romance', 'displayName' => 'Steamy Romance', 'interestId' => 'in0000154'),
                array('keyword' => 'stoner comedy', 'displayName' => 'Stoner Comedy', 'interestId' => 'in0000048'),
                array('keyword' => 'stop motion animation', 'displayName' => 'Stop Motion Animation', 'interestId' => 'in0000030'),
                array('keyword' => 'superhero action', 'displayName' => 'Superhero', 'interestId' => 'in0000008'),
                array('keyword' => 'supernatural fantasy', 'displayName' => 'Supernatural Fantasy', 'interestId' => 'in0000099'),
                array('keyword' => 'supernatural horror', 'displayName' => 'Supernatural Horror', 'interestId' => 'in0000117'),
                array('keyword' => 'survival adventure', 'displayName' => 'Survival', 'interestId' => 'in0000185'),
                array('keyword' => 'survival game show', 'displayName' => 'Survival Competition', 'interestId' => 'in0000106'),
                array('keyword' => 'suspense mystery', 'displayName' => 'Suspense Mystery', 'interestId' => 'in0000140'),
                array('keyword' => 'swashbuckler adventure', 'displayName' => 'Swashbuckler', 'interestId' => 'in0000022'),
                array('keyword' => 'sword and sandal action', 'displayName' => 'Sword & Sandal', 'interestId' => 'in0000009'),
                array('keyword' => 'sword and sorcery fantasy', 'displayName' => 'Sword & Sorcery', 'interestId' => 'in0000100'),
                array('keyword' => 'talent game show', 'displayName' => 'Talent Competition', 'interestId' => 'in0000107'),
                array('keyword' => 'Talk Show', 'displayName' => 'Talk Show', 'interestId' => 'in0000127'),
                array('keyword' => 'teen adventure', 'displayName' => 'Teen Adventure', 'interestId' => 'in0000023'),
                array('keyword' => 'teen comedy', 'displayName' => 'Teen Comedy', 'interestId' => 'in0000049'),
                array('keyword' => 'teen drama', 'displayName' => 'Teen Drama', 'interestId' => 'in0000089'),
                array('keyword' => 'teen fantasy', 'displayName' => 'Teen Fantasy', 'interestId' => 'in0000101'),
                array('keyword' => 'teen horror', 'displayName' => 'Teen Horror', 'interestId' => 'in0000118'),
                array('keyword' => 'teen romance', 'displayName' => 'Teen Romance', 'interestId' => 'in0000155'),
                array('keyword' => 'telenovela drama', 'displayName' => 'Telenovela', 'interestId' => 'in0000210'),
                array('keyword' => 'Thriller', 'displayName' => 'Thriller', 'interestId' => 'in0000186'),
                array('keyword' => 'time travel sci fi', 'displayName' => 'Time Travel', 'interestId' => 'in0000166'),
                array('keyword' => 'tragedy drama', 'displayName' => 'Tragedy', 'interestId' => 'in0000090'),
                array('keyword' => 'tragic romance', 'displayName' => 'Tragic Romance', 'interestId' => 'in0000156'),
                array('keyword' => 'travel reality tv', 'displayName' => 'Travel', 'interestId' => 'in0000128'),
                array('keyword' => 'travel documentary', 'displayName' => 'Travel Documentary', 'interestId' => 'in0000071'),
                array('keyword' => 'true crime', 'displayName' => 'True Crime', 'interestId' => 'in0000058'),
                array('keyword' => 'urban adventure', 'displayName' => 'Urban Adventure', 'interestId' => 'in0000024'),
                array('keyword' => 'vampire horror', 'displayName' => 'Vampire Horror', 'interestId' => 'in0000119'),
                array('keyword' => 'War', 'displayName' => 'War', 'interestId' => 'in0000010'),
                array('keyword' => 'epic war', 'displayName' => 'War Epic', 'interestId' => 'in0000011'),
                array('keyword' => 'aquatic sport', 'displayName' => 'Water Sport', 'interestId' => 'in0000175'),
                array('keyword' => 'werewolf horror', 'displayName' => 'Werewolf Horror', 'interestId' => 'in0000120'),
                array('keyword' => 'Western', 'displayName' => 'Western', 'interestId' => 'in0000191'),
                array('keyword' => 'epic western', 'displayName' => 'Western Epic', 'interestId' => 'in0000189'),
                array('keyword' => 'whodunnit mystery', 'displayName' => 'Whodunit', 'interestId' => 'in0000141'),
                array('keyword' => 'witch horror', 'displayName' => 'Witch Horror', 'interestId' => 'in0000121'),
                array('keyword' => 'workplace drama', 'displayName' => 'Workplace Drama', 'interestId' => 'in0000091'),
                array('keyword' => 'wuxia action', 'displayName' => 'Wuxia', 'interestId' => 'in0000200'),
                array('keyword' => 'zombie horror', 'displayName' => 'Zombie Horror', 'interestId' => 'in0000122'),
                array('keyword' => 'the addams family', 'displayName' => 'The Addams Family', 'interestId' => 'in0000313'),
                array('keyword' => 'alien', 'displayName' => 'Alien', 'interestId' => 'in0000265'),
                array('keyword' => 'avatar the last airbender', 'displayName' => 'Avatar: The Last Airbender', 'interestId' => 'in0000312'),
                array('keyword' => 'battlestar galactica', 'displayName' => 'Battlestar Galactica', 'interestId' => 'in0000278'),
                array('keyword' => 'big brother', 'displayName' => 'Big Brother', 'interestId' => 'in0000263'),
                array('keyword' => 'blade runner', 'displayName' => 'Blade Runner', 'interestId' => 'in0000296'),
                array('keyword' => 'breaking bad', 'displayName' => 'Breaking Bad', 'interestId' => 'in0000274'),
                array('keyword' => 'buffyverse', 'displayName' => 'Buffyverse', 'interestId' => 'in0000311'),
                array('keyword' => 'the chronicles of narnia', 'displayName' => 'Chronicles of Narnia', 'interestId' => 'in0000289'),
                array('keyword' => 'the conjuring universe', 'displayName' => 'The Conjuring Universe', 'interestId' => 'in0000287'),
                array('keyword' => 'dc', 'displayName' => 'DC', 'interestId' => 'in0000244'),
                array('keyword' => 'degrassi', 'displayName' => 'Degrassi', 'interestId' => 'in0000264'),
                array('keyword' => 'despicable me', 'displayName' => 'Despicable Me', 'interestId' => 'in0000267'),
                array('keyword' => 'doctor who', 'displayName' => 'Doctor Who', 'interestId' => 'in0000270'),
                array('keyword' => 'dragon ball', 'displayName' => 'Dragon Ball', 'interestId' => 'in0000250'),
                array('keyword' => 'dune', 'displayName' => 'Dune', 'interestId' => 'in0000297'),
                array('keyword' => 'evangelion', 'displayName' => 'Evangelion', 'interestId' => 'in0000255'),
                array('keyword' => 'evil dead', 'displayName' => 'Evil Dead', 'interestId' => 'in0000290'),
                array('keyword' => 'fast and furious', 'displayName' => 'Fast & Furious', 'interestId' => 'in0000273'),
                array('keyword' => 'frozen', 'displayName' => 'Frozen', 'interestId' => 'in0000293'),
                array('keyword' => 'godzilla', 'displayName' => 'Godzilla', 'interestId' => 'in0000285'),
                array('keyword' => 'golmaal', 'displayName' => 'Golmaal', 'interestId' => 'in0000306'),
                array('keyword' => 'gundam', 'displayName' => 'Gundam', 'interestId' => 'in0000256'),
                array('keyword' => 'harry potter', 'displayName' => 'Harry Potter', 'interestId' => 'in0000281'),
                array('keyword' => 'housefull', 'displayName' => 'Housefull', 'interestId' => 'in0000308'),
                array('keyword' => 'hunger games', 'displayName' => 'Hunger Games', 'interestId' => 'in0000303'),
                array('keyword' => 'indiana jones', 'displayName' => 'Indiana Jones', 'interestId' => 'in0000298'),
                array('keyword' => 'james bond', 'displayName' => 'James Bond', 'interestId' => 'in0000266'),
                array('keyword' => 'john wick', 'displayName' => 'John Wick', 'interestId' => 'in0000291'),
                array('keyword' => 'jurassic park', 'displayName' => 'Jurassic Park', 'interestId' => 'in0000283'),
                array('keyword' => 'kamen rider', 'displayName' => 'Kamen Rider', 'interestId' => 'in0000257'),
                array('keyword' => 'kung fu panda', 'displayName' => 'Kung Fu Panda', 'interestId' => 'in0000294'),
                array('keyword' => 'lego', 'displayName' => 'Lego', 'interestId' => 'in0000249'),
                array('keyword' => 'lord of the rings', 'displayName' => 'Lord of the Rings', 'interestId' => 'in0000279'),
                array('keyword' => 'love is blind', 'displayName' => 'Love Is Blind', 'interestId' => 'in0000261'),
                array('keyword' => 'love island', 'displayName' => 'Love Island', 'interestId' => 'in0000254'),
                array('keyword' => 'maddock horror comedy universe', 'displayName' => 'Maddock Horror Comedy Universe', 'interestId' => 'in0000305'),
                array('keyword' => 'marvel', 'displayName' => 'Marvel', 'interestId' => 'in0000248'),
                array('keyword' => 'masters of the universe', 'displayName' => 'Masters of the Universe', 'interestId' => 'in0000280'),
                array('keyword' => 'the matrix', 'displayName' => 'The Matrix', 'interestId' => 'in0000304'),
                array('keyword' => 'mission impossible', 'displayName' => 'Mission: Impossible', 'interestId' => 'in0000284'),
                array('keyword' => 'naruto', 'displayName' => 'Naruto', 'interestId' => 'in0000258'),
                array('keyword' => 'one piece', 'displayName' => 'One Piece', 'interestId' => 'in0000251'),
                array('keyword' => 'pitch perfect', 'displayName' => 'Pitch Perfect', 'interestId' => 'in0000302'),
                array('keyword' => 'planet of the apes', 'displayName' => 'Planet of the Apes', 'interestId' => 'in0000275'),
                array('keyword' => 'pokemon', 'displayName' => 'Pokémon', 'interestId' => 'in0000252'),
                array('keyword' => 'power rangers', 'displayName' => 'Power Rangers', 'interestId' => 'in0000246'),
                array('keyword' => 'predator', 'displayName' => 'Predator', 'interestId' => 'in0000276'),
                array('keyword' => 'the real housewives', 'displayName' => 'The Real Housewives', 'interestId' => 'in0000269'),
                array('keyword' => 'rohit shetty cop universe', 'displayName' => 'Rohit Shetty Cop Universe', 'interestId' => 'in0000307'),
                array('keyword' => 'rupauls drag race', 'displayName' => 'RuPaul\'s Drag Race', 'interestId' => 'in0000253'),
                array('keyword' => 'saw', 'displayName' => 'Saw', 'interestId' => 'in0000277'),
                array('keyword' => 'scooby doo', 'displayName' => 'Scooby Doo', 'interestId' => 'in0000309'),
                array('keyword' => 'scream', 'displayName' => 'Scream', 'interestId' => 'in0000292'),
                array('keyword' => 'shrek', 'displayName' => 'Shrek', 'interestId' => 'in0000268'),
                array('keyword' => 'spongebob squarepants', 'displayName' => 'SpongeBob SquarePants', 'interestId' => 'in0000288'),
                array('keyword' => 'star trek', 'displayName' => 'Star Trek', 'interestId' => 'in0000247'),
                array('keyword' => 'star wars', 'displayName' => 'Star Wars', 'interestId' => 'in0000243'),
                array('keyword' => 'stargate', 'displayName' => 'Stargate', 'interestId' => 'in0000295'),
                array('keyword' => 'super mario bros', 'displayName' => 'Super Mario Bros', 'interestId' => 'in0000299'),
                array('keyword' => 'teenage mutant ninja turtles', 'displayName' => 'Teenage Mutant Ninja Turtles', 'interestId' => 'in0000271'),
                array('keyword' => 'terminator', 'displayName' => 'Terminator', 'interestId' => 'in0000286'),
                array('keyword' => 'toy story', 'displayName' => 'Toy Story', 'interestId' => 'in0000272'),
                array('keyword' => 'transformers', 'displayName' => 'Transformers', 'interestId' => 'in0000245'),
                array('keyword' => 'the walking dead', 'displayName' => 'The Walking Dead', 'interestId' => 'in0000282'),
                array('keyword' => 'the witcher', 'displayName' => 'The Witcher', 'interestId' => 'in0000300'),
                array('keyword' => 'yash raj films spy universe', 'displayName' => 'Yash Raj Films Spy Universe', 'interestId' => 'in0000301'),
                array('keyword' => 'yu gi oh', 'displayName' => 'Yu Gi Oh!', 'interestId' => 'in0000259'),
                array('keyword' => '90 day fiance', 'displayName' => '90 Day Fiancé', 'interestId' => 'in0000262')
            );
            $lang = array (
                array('isoCode' => 'ar', 'displayName' => 'Arabic', 'interestId' => 'in0000213'),
                array('isoCode' => 'bn', 'displayName' => 'Bengali', 'interestId' => 'in0000214'),
                array('isoCode' => 'yue', 'displayName' => 'Cantonese', 'interestId' => 'in0000215'),
                array('isoCode' => 'da', 'displayName' => 'Danish', 'interestId' => 'in0000242'),
                array('isoCode' => 'nl', 'displayName' => 'Dutch', 'interestId' => 'in0000216'),
                array('isoCode' => 'fil', 'displayName' => 'Filipino', 'interestId' => 'in0000217'),
                array('isoCode' => 'fi', 'displayName' => 'Finnish', 'interestId' => 'in0000218'),
                array('isoCode' => 'fr', 'displayName' => 'French', 'interestId' => 'in0000219'),
                array('isoCode' => 'de', 'displayName' => 'German', 'interestId' => 'in0000220'),
                array('isoCode' => 'el', 'displayName' => 'Greek', 'interestId' => 'in0000221'),
                array('isoCode' => 'hi', 'displayName' => 'Hindi', 'interestId' => 'in0000222'),
                array('isoCode' => 'it', 'displayName' => 'Italian', 'interestId' => 'in0000223'),
                array('isoCode' => 'ja', 'displayName' => 'Japanese', 'interestId' => 'in0000224'),
                array('isoCode' => 'kn', 'displayName' => 'Kannada', 'interestId' => 'in0000241'),
                array('isoCode' => 'ko', 'displayName' => 'Korean', 'interestId' => 'in0000225'),
                array('isoCode' => 'ml', 'displayName' => 'Malayalam', 'interestId' => 'in0000240'),
                array('isoCode' => 'cmn', 'displayName' => 'Mandarin', 'interestId' => 'in0000226'),
                array('isoCode' => 'mr', 'displayName' => 'Marathi', 'interestId' => 'in0000227'),
                array('isoCode' => 'no', 'displayName' => 'Norwegian', 'interestId' => 'in0000228'),
                array('isoCode' => 'fa', 'displayName' => 'Persian', 'interestId' => 'in0000229'),
                array('isoCode' => 'pt', 'displayName' => 'Portuguese', 'interestId' => 'in0000230'),
                array('isoCode' => 'pa', 'displayName' => 'Punjabi', 'interestId' => 'in0000231'),
                array('isoCode' => 'ru', 'displayName' => 'Russian', 'interestId' => 'in0000232'),
                array('isoCode' => 'es', 'displayName' => 'Spanish', 'interestId' => 'in0000233'),
                array('isoCode' => 'sv', 'displayName' => 'Swedish', 'interestId' => 'in0000234'),
                array('isoCode' => 'ta', 'displayName' => 'Tamil', 'interestId' => 'in0000235'),
                array('isoCode' => 'te', 'displayName' => 'Telugu', 'interestId' => 'in0000236'),
                array('isoCode' => 'th', 'displayName' => 'Thai', 'interestId' => 'in0000237'),
                array('isoCode' => 'tr', 'displayName' => 'Turkish', 'interestId' => 'in0000238'),
                array('isoCode' => 'ur', 'displayName' => 'Urdu', 'interestId' => 'in0000239')
            );
            if (empty($this->languages)) {
                $this->language();
            }
            if (!empty($this->languages[0])) {
                foreach ($lang as $langArray) {
                    if($this->languages[0]['id'] === $langArray['isoCode']) {
                        $this->interests[] = array(
                            'interestId' => $langArray['interestId'],
                            'displayName' => $langArray['displayName']
                        );
                    }
                }
            }
            $query = <<<EOF
itemCategory {
  itemCategoryId
}
keyword {
  text {
    text
  }
}
EOF;
            $data = $this->graphQlGetAll("Interests", "keywords", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    if (isset($edge->node->itemCategory->itemCategoryId) &&
                        $edge->node->itemCategory->itemCategoryId == 'subgenre'
                       )
                    {
                        if (isset($edge->node->keyword->text->text)) {
                            foreach ($keywords as $keyword) {
                               if(in_array($edge->node->keyword->text->text, $keyword)) {
                                  $this->interests[] = array(
                                    'interestId' => $keyword['interestId'],
                                    'displayName' => $keyword['displayName']
                                  );
                               }
                            }
                        }
                    }
                }
            }
        }
        $this->interests = $this->sortByName($this->interests, "ASC");
        if (empty($this->genres)) {
            $this->genre();
        }
        foreach ($this->genres as $item) {
            if (isset($item['mainGenre']) && $item['mainGenre'] != '') {
                foreach ($keywords as $keyword) {
                   if(in_array($item['mainGenre'], $keyword)) {
                      $this->interests[] = array(
                        'interestId' => $keyword['interestId'],
                        'displayName' => $keyword['displayName']
                      );
                   }
                }
            }
        }
        return $this->interests;
    }

    #-------------------------------------------------------[ Interesting keywords ]---
    /**
     * Get all interesting keywords of this title
     * @note It is a mix of keywords and main genres and an alternative for the real interests from imdb
     * @return array
     */
    public function interestingKeywords()
    {
        if (empty($this->interestKeywords)) {
            $query = <<<EOF
itemCategory {
  itemCategoryId
}
keyword {
  text {
    text
  }
}
EOF;
            $data = $this->graphQlGetAll("InterestKeyword", "keywords", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    if (isset($edge->node->itemCategory->itemCategoryId) &&
                        $edge->node->itemCategory->itemCategoryId == 'subgenre'
                       )
                    {
                        if (isset($edge->node->keyword->text->text)) {
                            $this->interestKeywords[] = ucwords($edge->node->keyword->text->text);
                        }
                    }
                }
            }
        }
        sort($this->interestKeywords);
        if (empty($this->genres)) {
            $this->genre();
        }
        foreach ($this->genres as $item) {
            if (isset($item['mainGenre']) && $item['mainGenre'] != '') {
                $this->interestKeywords[] = $item['mainGenre'];
            }
        }
        return $this->interestKeywords;
    }

    #-------------------------------------------------------[ Keywords ]---
    /**
     * Get all keywords of this title
     * @return array
     */
    public function keyword()
    {
        if (empty($this->keywords)) {
            $query = <<<EOF
itemCategory {
  itemCategoryId
}
keyword {
  id
  text {
    text
  }
}
EOF;
            $data = $this->graphQlGetAll("Keywords", "keywords", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $category = isset($edge->node->itemCategory->itemCategoryId) ?
                                      $edge->node->itemCategory->itemCategoryId : 'Unknown';
                    if (!empty($edge->node->keyword->text->text)) {
                        $this->keywords[$category][] = array(
                            'id' => isset($edge->node->keyword->id) ?
                                          $edge->node->keyword->id : null,
                            'text' => $edge->node->keyword->text->text
                        );
                    }
                }
            }
        }
        return $this->keywords;
    }

    #-------------------------------------------------------[ Alternate versions ]---
    /**
     * Get the Alternate Versions of this title
     * @return array
     */
    public function alternateVersion()
    {
        if (empty($this->alternateversions)) {
            $query = <<<EOF
text {
  plainText
}
EOF;
            $data = $this->graphQlGetAll("AlternateVersions", "alternateVersions", $query);
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    if (!empty($edge->node->text->plainText)) {
                        $this->alternateversions[] = $edge->node->text->plainText;
                    }
                }
            }
        }
        return $this->alternateversions;
    }

    #-------------------------------------------------[ Photo images ]---
    /**
     * Get title photos image URLs
     * @note Default: 6 images, max=9999 (set in config)
     * @param int $amount, how many images
     * @param bool $thumb
     *      true: height is always the same (set in config), width is variable!
     *      false: untouched max width 1000 pixels
     * @return array
     */
    public function mainphoto($amount = 6, $thumb = true)
    {
        if (empty($this->mainPhoto)) {
            $query = <<<EOF
query MainPhoto(\$id: ID!) {
  title(id: \$id) {
    images(first: $amount) {
      edges {
        node {
          url
          width
          height
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "MainPhoto", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->mainPhoto;
            }
            if (isset($data->title->images->edges) &&
                is_array($data->title->images->edges) &&
                count($data->title->images->edges) > 0
               )
            {
                foreach ($data->title->images->edges as $edge) {
                    if (!empty($edge->node->url)) {
                        $imgUrl = str_replace('.jpg', '', $edge->node->url);
                        if ($thumb === true) {
                            $fullImageWidth = $edge->node->width;
                            $fullImageHeight = $edge->node->height;
                            $newImageHeight = $this->config->mainphotoThumbnailHeight;
                            // calculate new width
                            $newImageWidth = $this->imageFunctions->thumbUrlNewWidth($fullImageWidth, $fullImageHeight, $newImageHeight);
                            $this->mainPhoto[] = $imgUrl . 'QL75_UX' . $newImageWidth . '_.jpg';
                        } else {
                            $this->mainPhoto[] = $imgUrl . 'QL100_UX1000_.jpg';
                        }
                    }
                }
            }
        }
        return $this->mainPhoto;
    }

    #-------------------------------------------------[ Video ]---
    /**
     * Get all video URL's including images from videogallery page
     * @see config settings for this method
     * @return array
     *     [Trailer]  Array()
     *          [0]  Array()
     *              [id]            string 4030506521
     *              [name]          string A Clockwork Orange
     *              [runtime]       int 130 (seconds)
     *              [description]   string Trailer for A Clockwork Orange - Two-Disc Anniversary Edition Blu-ray Book Packaging
     *              [titleName]     string A Clockwork Orange
     *              [titleYear]     int 1971
     *              [playbackUrl]   string https://www.imdb.com/video/vi4030506521/ (this is playable in browser)
     *              [fullPlaybackUrls]  Array()
     *                  [0]  Array()
     *                      [url]           string https://imdb-video.media-imdb.com/vi3115516185/1434659454657-dx9ykf-1564078123074.mp4
     *                      [mimeType]      string MP4
     *                      [definition]  s tring  DEF_SD
     *              [fullPreviewUrls]  Array()
     *                  [0] => Array()
     *                      [url]           string
     *                      [mimeType]      string M3U8
     *                      [definition]    string DEF_AUTO
     *              [imageUrl]      string https://m.media-amazon.com/images/M/MVTg@._V1_QL75_UX500_CR0,47,500,281_.jpg
     */
    public function video()
    {
        if (empty($this->videos)) {
            $filter = $this->config->videoIncludeMature === true ? ',filter:{maturityLevel:INCLUDE_MATURE}' : '';
            $query = <<<EOF
query Video(\$id: ID!) {
  title(id: \$id) {
    videoStrip(first:9999$filter) {
      edges {
        node {
          id
          playbackURLs {
            url
            videoMimeType
            videoDefinition
          }
          previewURLs {
            url
            videoMimeType
            videoDefinition
          }
          name {
            value
          }
          runtime {
            value
          }
          contentType {
            displayName {
              value
            }
          }
          description {
            value
          }
          thumbnail {
            url
            width
            height
          }
          primaryTitle {
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
  }
}
EOF;
            $data = $this->graphql->query($query, "Video", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->videos;
            }
            if (isset($data->title->videoStrip->edges) &&
                is_array($data->title->videoStrip->edges) &&
                count($data->title->videoStrip->edges) > 0
               )
            {
                foreach ($data->title->videoStrip->edges as $edge) {
                    if ($this->config->videoContentType == 'trailer' &&
                        isset($edge->node->contentType->displayName->value) &&
                        $edge->node->contentType->displayName->value !== "Trailer"
                       )
                    {
                        continue;
                    }
                    // fullPlaybackURLs
                    $fullPlaybackUrl = array();
                    if (isset($edge->node->playbackURLs) &&
                        is_array($edge->node->playbackURLs) &&
                        count($edge->node->playbackURLs) > 0
                       )
                    {
                        foreach ($edge->node->playbackURLs as $playbackUrl) {
                            if (!empty($playbackUrl->url)) {
                                $fullPlaybackUrl[] = array(
                                    'url' => $playbackUrl->url,
                                    'mimeType' => isset($playbackUrl->videoMimeType) ?
                                                        $playbackUrl->videoMimeType : null,
                                    'definition' => isset($playbackUrl->videoDefinition) ?
                                                          $playbackUrl->videoDefinition : null,
                                );
                            }
                        }
                    }
                    // fullPreviewURLs
                    $fullPreviewUrls = array();
                    if (isset($edge->node->previewURLs) &&
                        is_array($edge->node->previewURLs) &&
                        count($edge->node->previewURLs) > 0
                       )
                    {
                        foreach ($edge->node->previewURLs as $previewUrl) {
                            if (!empty($previewUrl->url)) {
                                $fullPreviewUrls[] = array(
                                    'url' => $previewUrl->url,
                                    'mimeType' => isset($previewUrl->videoMimeType) ?
                                                        $previewUrl->videoMimeType : null,
                                    'definition' => isset($previewUrl->videoDefinition) ?
                                                          $previewUrl->videoDefinition : null,
                                );
                            }
                        }
                    }
                    $thumbUrl = null;
                    $videoId = isset($edge->node->id) ?
                                     str_replace('vi', '', $edge->node->id) : null;
                    if (!empty($edge->node->thumbnail->url)) {
                        $fullImageWidth = $edge->node->thumbnail->width;
                        $fullImageHeight = $edge->node->thumbnail->height;
                        $newImageWidth = $this->config->videoThumbnailWidth;
                        $newImageHeight = $this->config->videoThumbnailHeight;
                        $img = str_replace('.jpg', '', $edge->node->thumbnail->url);
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                        $thumbUrl = $img . $parameter;
                    }
                    $this->videos[$edge->node->contentType->displayName->value][] = array(
                        'id' => $videoId,
                        'name' => isset($edge->node->name->value) ?
                                        $edge->node->name->value : null,
                        'runtime' => isset($edge->node->runtime->value) ?
                                           $edge->node->runtime->value : null,
                        'description' => isset($edge->node->description->value) ?
                                               $edge->node->description->value : null,
                        'titleName' => isset($edge->node->primaryTitle->titleText->text) ?
                                             $edge->node->primaryTitle->titleText->text : null,
                        'titleYear' => isset($edge->node->primaryTitle->releaseYear->year) ?
                                             $edge->node->primaryTitle->releaseYear->year : null,
                        'playbackUrl' => !empty($videoId) ?
                                         'https://www.imdb.com/video/vi' . $videoId . '/' : null,
                        'fullPlaybackUrls' => $fullPlaybackUrl,
                        'fullPreviewUrls' => $fullPreviewUrls,
                        'imageUrl' => $thumbUrl
                    );
                }
            }
        }
        return $this->videos;
    }

    #-------------------------------------------------------[ Prestigious/Main Awards ]---
    /**
     * Get prestigious awards (not including total wins and total nominations)
     * @return array
     */
    public function mainaward()
    {
        if (empty($this->mainAwards)) {
            $query = <<<EOF
query MainAward(\$id: ID!) {
  title(id: \$id) {
    prestigiousAwardSummary {
      award {
        text
      }
      nominations
      wins
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "MainAward", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->mainAwards;
            }
            if (!empty($data->title->prestigiousAwardSummary)) {
                $this->mainAwards = array(
                    'award' => isset($data->title->prestigiousAwardSummary->award->text) ?
                                     $data->title->prestigiousAwardSummary->award->text : null,
                    'nominations' => isset($data->title->prestigiousAwardSummary->nominations) ?
                                           $data->title->prestigiousAwardSummary->nominations : null,
                    'wins' => isset($data->title->prestigiousAwardSummary->wins) ?
                                    $data->title->prestigiousAwardSummary->wins : null
                );
            }
        }
        return $this->mainAwards;
    }

    #-------------------------------------------------------[ Awards ]---
    /**
     * Get all awards for a title
     * @param bool $winsOnly true: only get won awards, false: get all awards
     * @param $event Default: "" (eventId Example: " ev0000003" to only get Oscars)
     *  Possible values for $event:
     *      ev0000003 (Oscar)
     *      ev0000223 (Emmy)
     *      ev0000292 (Golden Globe)
     *      ev0000471 (National Society of Film Critics Awards, USA)
     *      ev0000004 (Academy of Science Fiction, Fantasy & Horror Films, USA)
     *      ev0000123 (BAFTA Awards)
     *      ev0000296 (Satellite Awards)
     *      ev0000453 (MTV Movie + TV Awards)
     *      ev0000511 (Online Film Critics Society Awards)
     *      ev0000786 (Empire Awards, UK)
     *      ev0001644 (DVD Exclusive Awards)
     *      ev0002704 (Online Film & Television Association)
     *      ev0002990 (Awards Circuit Community Awards)
     *      ev0003023 (Golden Schmoes Awards)
     *      ev0000133 (Critics Choice Awards)
     *      ev0000403 (London Critics Circle Film Awards)
     *      ev0000530 (People's Choice Awards, USA)
     * @return array
     *           [Academy Awards, USA]  Array()
     *                   [0]  Array()
     *                       [awardYear] int 1972
     *                       [awardWinner]       String
     *                       [awardCategory]     string Best Picture
     *                       [awardName]         string Oscar
     *                       [awardPerons]  Array()
     *                           [0] => Array()
     *                                   [creditId]              string 0000040
     *                                   [creditName]            string Stanley Kubrick
     *                                   [creditNote]            string screenplay/director
     *                                   [nameFullImageUrl]      string max width 1000 pixels
     *                                   [nameThumbImageUrl] =>  string 140x207 pixels fixed
     *                       [awardNotes]    string Based on the novel
     *                       [awardOutcome]  string Nominee
     *           [total] => Array()
     *               [win] int 12
     *               [nom] int 26
     */
    public function award($winsOnly = false, $event = "")
    {
        $filter = $this->awardFilter($winsOnly, $event);
        if (empty($this->awards)) {
            $query = <<<EOF
award {
  event {
    text
  }
  text
  category {
    text
  }
  eventEdition {
    year
  }
  notes {
    plainText
  }
}
isWinner
awardedEntities {
  ... on AwardedTitles {
    secondaryAwardNames {
      name {
        id
        nameText {
          text
        }
        primaryImage {
          url
          width
          height
        }
      }
      note {
        plainText
      }
    }
  }
}
EOF;
            $data = $this->graphQlGetAll("Award", "awardNominations", $query, $filter);
            $winnerCount = 0;
            $nomineeCount = 0;
            if (count($data) > 0) {
                foreach ($data as $edge) {
                    $eventName = isset($edge->node->award->event->text) ?
                                    $edge->node->award->event->text : null;
                    $awardIsWinner = $edge->node->isWinner;
                    $conclusion = $awardIsWinner === true ? "Winner" : "Nominee";
                    $awardIsWinner === true ? $winnerCount++ : $nomineeCount++;
                    //credited persons
                    $names = array();
                    if (isset($edge->node->awardedEntities->secondaryAwardNames) &&
                        is_array($edge->node->awardedEntities->secondaryAwardNames) &&
                        count($edge->node->awardedEntities->secondaryAwardNames) > 0
                       )
                    {
                        foreach ($edge->node->awardedEntities->secondaryAwardNames as $creditor) {
                            $nameThumbImageUrl = null;
                            $nameFullImageUrl = null;
                            if (!empty($creditor->name->primaryImage->url)) {
                                $img = str_replace('.jpg', '', $creditor->name->primaryImage->url);
                                $nameFullImageUrl = $img . 'QL100_UX1000_.jpg';
                                $fullImageWidth = $creditor->name->primaryImage->width;
                                $fullImageHeight = $creditor->name->primaryImage->height;
                                $newImageWidth = 140;
                                $newImageHeight = 207;
                                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                                $nameThumbImageUrl = $img . $parameter;
                            }
                            $names[] = array(
                                'creditId' => isset($creditor->name->id) ?
                                                    str_replace('nm', '', $creditor->name->id) : null,
                                'creditName' => isset($creditor->name->nameText->text) ?
                                                      $creditor->name->nameText->text : null,
                                'creditNote' => isset($creditor->note->plainText) ?
                                                      trim($creditor->note->plainText, " ()") : null,
                                'nameFullImageUrl' => $nameFullImageUrl,
                                'nameThumbImageUrl' => $nameThumbImageUrl
                            );
                        }
                    }
                    
                    $this->awards[$eventName][] = array(
                        'awardYear' => isset($edge->node->award->eventEdition->year) ?
                                             $edge->node->award->eventEdition->year : null,
                        'awardWinner' => $awardIsWinner,
                        'awardCategory' => isset($edge->node->award->category->text) ?
                                                 $edge->node->award->category->text : null,
                        'awardName' => isset($edge->node->award->text) ?
                                             $edge->node->award->text : null,
                        'awardNotes' => isset($edge->node->award->notes->plainText) ?
                                              $edge->node->award->notes->plainText : null,
                        'awardPersons' => $names,
                        'awardOutcome' => $conclusion
                    );
                }
            }
            if ($winnerCount > 0 || $nomineeCount > 0) {
                $this->awards['total'] = array(
                    'win' => $winnerCount,
                    'nom' => $nomineeCount
                );
            }
        }
        return $this->awards;
    }

    #----------------------------------------------------------[ Sound ]---
    /**
     * Get movie sound mixes
     * @return array
     */
    public function sound()
    {
        if (empty($this->soundMix)) {
            return $this->techSpec("soundMixes", "text", $this->soundMix);
        }
        return $this->soundMix;
    }
    
    #----------------------------------------------------------[ Colorations ]---
    /**
     * Get movie colorations like color or Black and white
     * @return array
     */
    public function color()
    {
        if (empty($this->colors)) {
            return $this->techSpec("colorations", "text", $this->colors);
        }
        return $this->colors;
    }
    
    #----------------------------------------------------------[ Aspect ratio ]---
    /**
     * Get movie aspect ratio like 1.66:1 or 16:9
     * @return array
     */
    public function aspectRatio()
    {
        if (empty($this->aspectRatio)) {
            return $this->techSpec("aspectRatios", "aspectRatio", $this->aspectRatio);
        }
        return $this->aspectRatio;
    }
    
    #----------------------------------------------------------[ Cameras ]---
    /**
     * Get cameras used in this title
     * @return array
     */
    public function camera()
    {
        if (empty($this->cameras)) {
            return $this->techSpec("cameras", "camera", $this->cameras);
        }
        return $this->cameras;
    }

    #----------------------------------------------------------[ Movie Featured Reviews ]---
    /**
     * Get movie featured reviews (max 5 available)
     * @return array
     */
    public function featuredReview()
    {
        if (empty($this->featuredReviews)) {
            $query = <<<EOF
query Reviews(\$id: ID!) {
  title(id: \$id) {
    featuredReviews(first: 5) {
      edges {
        node {
          summary {
            originalText
          }
          author {
            nickName
          }
          authorRating
          text {
            originalText {
              plainText
            }
          }
          submissionDate
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Reviews", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->featuredReviews;
            }
            if (isset($data->title->featuredReviews->edges) &&
                is_array($data->title->featuredReviews->edges) &&
                count($data->title->featuredReviews->edges) > 0
               )
            {
                foreach ($data->title->featuredReviews->edges as $edge) {
                $this->featuredReviews[] = array(
                    'authorNickName' => isset($edge->node->author->nickName) ?
                                              $edge->node->author->nickName : null,
                    'authorRating' => isset($edge->node->authorRating) ?
                                            $edge->node->authorRating : null,
                    'summaryText' => isset($edge->node->summary->originalText) ?
                                           $edge->node->summary->originalText : null,
                    'reviewText' => isset($edge->node->text->originalText->plainText) ?
                                          $edge->node->text->originalText->plainText : null,
                    'submissionDate' => isset($edge->node->submissionDate) ?
                                              $edge->node->submissionDate : null
                    );
                }
            }
        }
        return $this->featuredReviews;
    }

    #----------------------------------------------------------[ Movie isAdult ]---
    /**
     * Get adult status of a title
     * @return bool|null
     * @see IMDB page / (TitlePage)
     */
    public function isAdult()
    {
        if (empty($this->isAdult)) {
            $query = <<<EOF
query Adult(\$id: ID!) {
  title(id: \$id) {
    isAdult
  }
}
EOF;
            $data = $this->graphql->query($query, "Adult", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->isAdult;
            }
            $this->isAdult = $data->title->isAdult;
        }
        return $this->isAdult;
    }

    #----------------------------------------------------------[ Watch Option ]---
    /**
     * watch options by category for this title
     * @Note: (DEC 2024) Only Amazon providers are returned, no others!
     * @return array
     *  [rent/buy]  array()
     *      [0]  array()
     *          [providerId]      string amzn1.imdb.w2w.provider.prime_video
     *          [providerName]    string Prime Video
     *          [logoUrl]         string (PNG!) https://m.media-amazon.com/images/M/4c6e._V1_QL100_UX250_.png
     */
    public function watchOption()
    {
        if (empty($this->watchOption)) {
            $query = <<<EOF
query WatchOption(\$id: ID!) {
  title(id: \$id) {
    watchOptionsByCategory(limit: 250) {
      categorizedWatchOptionsList {
        categoryName {
          value
        }
        watchOptions(limit: 250) {
          provider {
            id
            name {
              value
            }
            logos {
              icon {
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
            $data = $this->graphql->query($query, "WatchOption", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->watchOption;
            }
            if (isset($data->title->watchOptionsByCategory->categorizedWatchOptionsList) &&
                is_array($data->title->watchOptionsByCategory->categorizedWatchOptionsList) &&
                count($data->title->watchOptionsByCategory->categorizedWatchOptionsList) > 0
               )
            {
                foreach ($data->title->watchOptionsByCategory->categorizedWatchOptionsList as $item) {
                    $watchOptions = array();
                    $categoryName = strtolower(str_replace('/', '-', $item->categoryName->value));
                    if (isset($item->watchOptions) &&
                        is_array($item->watchOptions) &&
                        count($item->watchOptions) > 0
                       )
                    {
                        foreach ($item->watchOptions as $option) {
                            $logoUrl = null;
                            if (!empty($option->provider->logos->icon->url)) {
                                $img = str_replace('.png', '', $option->provider->logos->icon->url);
                                $logoUrl = $img . 'QL100_UX250_.png';
                            }
                            $watchOptions[] = array(
                                'providerId' => isset($option->provider->id) ?
                                                      $option->provider->id : null,
                                'providerName' => isset($option->provider->name->value) ?
                                                        $option->provider->name->value : null,
                                'logoUrl' => $logoUrl
                            );
                        }
                    }
                    $this->watchOption[$categoryName] = $watchOptions;
                }
            }
        }
        return $this->watchOption;
    }

    #----------------------------------------------------------[ Production Status ]---
    /**
     * Get current production status of a title e.g. Released, In Development, Pre-Production, Complete, Production etc
     * @return string|null
     */
    public function productionStatus()
    {
        if (empty($this->status)) {
            $query = <<<EOF
query ProductionStatus(\$id: ID!) {
  title(id: \$id) {
    productionStatus {
      currentProductionStage {
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "ProductionStatus", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->status;
            }
            $this->status = isset($data->title->productionStatus->currentProductionStage->text) ? 
                                  $data->title->productionStatus->currentProductionStage->text : null;
        }
        return $this->status;
    }

    #----------------------------------------------------------[ News ]---
    /**
     * Get news items about this title, max 100 items!
     * @return array
     *      [id]                  string
     *      [title]               string e.g. The best movies on Netflix right now
     *      [author]              string e.g. The A.V. Club
     *      [date]                string e.g. 2024-12-01T02:00:00Z
     *      [extUrl]              string e.g. https://www.avclub.com/1842540580
     *      [extHomepageUrl]      string e.g. http://www.avclub.com/content/home
     *      [extHomepageLabel]    string e.g. avclub.com
     *      [textHtml]            string (including html)
     *      [textText]            string
     *      [thumbnailUrl]        string
     */
    public function news()
    {
        if (empty($this->news)) {
            $query = <<<EOF
query News(\$id: ID!) {
  title(id: \$id) {
    news(first: 100) {
      edges {
        node {
          id
          articleTitle {
            plainText
          }
          byline
          date
          externalUrl
          image {
            url
            width
            height
          }
          source {
            description
            homepage {
              label
              url
            }
          }
          text {
            plainText
            plaidHtml
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "News", ["id" => "tt$this->imdbID"]);
            if (!isset($data->title)) {
                return $this->news;
            }
            if (isset($data->title->news->edges) &&
                is_array($data->title->news->edges) &&
                count($data->title->news->edges) > 0
               )
            {
                foreach ($data->title->news->edges as $edge) {
                    $thumbUrl = null;
                    if (!empty($edge->node->image->url)) {
                        $fullImageWidth = $edge->node->image->width;
                        $fullImageHeight = $edge->node->image->height;
                        $img = str_replace('.jpg', '', $edge->node->image->url);
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, 500, 281);
                        $thumbUrl = $img . $parameter;
                    }
                    $this->news[] = array(
                        'id' => isset($edge->node->id) ?
                                      str_replace('ni', '', $edge->node->id) : null,
                        'title' => isset($edge->node->articleTitle->plainText) ?
                                         $edge->node->articleTitle->plainText : null,
                        'author' => isset($edge->node->byline) ?
                                          $edge->node->byline : null,
                        'date' => isset($edge->node->date) ?
                                        $edge->node->date : null,
                        'extUrl' => isset($edge->node->externalUrl) ?
                                          $edge->node->externalUrl : null,
                        'extHomepageUrl' => isset($edge->node->source->homepage->url) ?
                                                  $edge->node->source->homepage->url : null,
                        'extHomepageLabel' => isset($edge->node->source->homepage->label) ?
                                                    $edge->node->source->homepage->label : null,
                        'textHtml' => isset($edge->node->text->plaidHtml) ?
                                            $edge->node->text->plaidHtml : null,
                        'textText' => isset($edge->node->text->plainText) ?
                                            $edge->node->text->plainText : null,
                        'thumbnailUrl' => $thumbUrl
                    );
                }
            }
        }
        return $this->news;
    }

    #-----------------------------------------[ Helper for photo() ]---
    /**
     * Fetch cover photo (thumbnail and big variant)
     */
    private function populatePoster()
    {
        $query = <<<EOF
query Poster(\$id: ID!) {
  title(id: \$id) {
    primaryImage {
      url
      width
      height
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Poster", ["id" => "tt$this->imdbID"]);
        if (!empty($data->title->primaryImage->url)) {
            $img = str_replace('.jpg', '', $data->title->primaryImage->url);
            // full image
            $this->mainPoster = $img . 'QL100_SX1000_.jpg';
            // thumb image
            if (!empty($data->title->primaryImage->width) && !empty($data->title->primaryImage->height)) {
                $fullImageWidth = $data->title->primaryImage->width;
                $fullImageHeight = $data->title->primaryImage->height;
                $newImageWidth = $this->config->photoThumbnailWidth;
                $newImageHeight = $this->config->photoThumbnailHeight;
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                $this->mainPosterThumb = $img . $parameter;
            }
        }
    }

    #-----------------------------------------[ Helper for companyCredits ]---
    /**
     * Fetch all company credits
     * @param string $category e.g. distribution, production
     * @return array
     */
    protected function companyCredits($category)
    {
        $filter = ', filter: { categories: ["' .$category . '"] }';
        $query = <<<EOF
company {
  id
}
displayableProperty {
  value {
    plainText
  }
}
countries(limit: 1) {
  text
}
attributes {
  text
}
yearsInvolved {
  year
}
EOF;
        $data = $this->graphQlGetAll("CompanyCredits", "companyCredits", $query, $filter);
        $results = array();
        if (count($data) > 0) {
            foreach ($data as $edge) {
                $companyAttribute = array();
                if (isset($edge->node->attributes) &&
                    is_array($edge->node->attributes) &&
                    count($edge->node->attributes) > 0
                   )
                {
                    foreach ($edge->node->attributes as $attribute) {
                        $companyAttribute[] = $attribute->text;
                    }
                }
                $results[] = array(
                    "name" => isset($edge->node->displayableProperty->value->plainText) ?
                                    $edge->node->displayableProperty->value->plainText : null,
                    "id" => isset($edge->node->company->id) ?
                                  str_replace('co', '', $edge->node->company->id ) : null,
                    "country" => isset($edge->node->countries[0]->text) ?
                                       $edge->node->countries[0]->text : null,
                    "attribute" => $companyAttribute,
                    "year" => isset($edge->node->yearsInvolved->year) ?
                                    $edge->node->yearsInvolved->year : null
                );
            }
        }
        return $results;
    }

    #---------------------------------------------------------------[ helper for crew credits ]---
    /** helper for stunts, thanks, visualEffects, specialEffects, producer,
     *      writer, director, composer, cinematographer, voiceActor
     * @param string $aditionalType filter type for miscelleanous credits, only "voice actor" is supported
     * @return array
     */
    private function creditHelper($crewCategory, $aditionalType = '')
    {
        $filter = ', filter: { categories: ["' .$crewCategory . '"] }';
        $output = array();
        $query = <<<EOF
name {
  nameText {
    text
  }
  id
  primaryImage {
    url
    width
    height
  }
}
... on Crew {
  jobs {
    text
  }
  attributes {
    text
  }
  episodeCredits(first: 9999) {
    total
    yearRange {
      year
      endYear
    }
  }
}
EOF;
        $data = $this->graphQlGetAll("CreditCrew", "credits", $query, $filter);
        if (count($data) > 0) {
            foreach ($data as $edge) {
                $jobs = array();
                if (isset($edge->node->jobs) &&
                    is_array($edge->node->jobs) &&
                    count($edge->node->jobs) > 0
                   )
                {
                    foreach ($edge->node->jobs as $value) {
                        if (!empty($value->text)) {
                            $jobs[] = $value->text;
                        }
                    }
                }
                // aditionalType "voice actor"
                if ($aditionalType === 'voice actor') {
                    foreach ($jobs as $jobKey => $job) {
                        $type = explode(':', $job);
                        if (trim($type[0]) !== $aditionalType) {
                            if ($jobKey === array_key_last($jobs)) {
                                continue 2;
                            } else {
                                continue;
                            }
                        } else {
                            break;
                        }
                    }
                }
                $episodes = array();
                if (!empty($edge->node->episodeCredits)) {
                    $episodes = array(
                        'total' => isset($edge->node->episodeCredits->total) ?
                                         $edge->node->episodeCredits->total : null,
                        'year' => isset($edge->node->episodeCredits->yearRange->year) ?
                                        $edge->node->episodeCredits->yearRange->year : null,
                        'endYear' => isset($edge->node->episodeCredits->yearRange->endYear) ?
                                           $edge->node->episodeCredits->yearRange->endYear : null
                    );
                }
                $attributes = array();
                if (isset($edge->node->attributes) &&
                    is_array($edge->node->attributes) &&
                    count($edge->node->attributes) > 0
                   )
                {
                    foreach ($edge->node->attributes as $attribute) {
                        if (!empty($attribute->text)) {
                            $attributes[] = $attribute->text;
                        }
                    }
                }
                $nameThumbImageUrl = null;
                $nameFullImageUrl = null;
                if (!empty($edge->node->name->primaryImage->url)) {
                    $img = str_replace('.jpg', '', $edge->node->name->primaryImage->url);
                    $nameFullImageUrl = $img . 'QL100_UX1000_.jpg';
                    $fullImageWidth = $edge->node->name->primaryImage->width;
                    $fullImageHeight = $edge->node->name->primaryImage->height;
                    $newImageWidth = 140;
                    $newImageHeight = 207;
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                    $nameThumbImageUrl = $img . $parameter;
                }
                $output[] = array(
                    'imdb' => isset($edge->node->name->id) ?
                                    str_replace('nm', '', $edge->node->name->id) : null,
                    'name' => isset($edge->node->name->nameText->text) ?
                                    $edge->node->name->nameText->text : null,
                    'jobs' => $jobs,
                    'attributes' => $attributes,
                    'episode' => $episodes,
                    'titleFullImageUrl' =>$nameFullImageUrl,
                    'titleThumbImageUrl' => $nameThumbImageUrl
                );
            }
        }
        return $output;
    }

    #----------------------------------------------------------[ Helper for Episode() build filter ]---
    /**
     * Build filter constraint for episode()
     * @param int $seasonYear node->text from season or year
     * @return string
     */
    public function buildFilter($seasonYear)
    {
        if (strlen((string)$seasonYear) === 4) {
            // year based Tv Series
            $filter = 'filter:{releasedOnOrAfter:{day:1,month:1,year:' . $seasonYear . '},'
                              . 'releasedOnOrBefore:{day:31,month:12,year:' . $seasonYear . '}}';
        } else {
            // To fetch data from unknown seasons/years
            if ($seasonYear == "Unknown") { //this is intended capitol
                $SeasonUnknown = "unknown"; //this is intended not capitol
                $seasonFilter = "";
            } else {
                $seasonFilter = $seasonYear;
                $SeasonUnknown = "";
            }
            $filter = 'filter:{includeSeasons:["' . $seasonFilter . '","' . $SeasonUnknown . '"]}';
        }
        return $filter;
    }

    #----------------------------------------------------------[ Helper for Episode() Season or year check ]---
    /** Check if TV Series season or year based is wanted by the user
     * @param int $yearbased 0: year based, 1: season based
     * @return array|false
     */
    private function seasonYearCheck($yearbased)
    {
        $querySeasons = <<<EOF
query Seasons(\$id: ID!) {
  title(id: \$id) {
    episodes {
      displayableSeasons(first: 9999) {
        edges {
          node {
            text
          }
        }
      }
      displayableYears(first: 9999) {
        edges {
          node {
            text
          }
        }
      }
    }
  }
}
EOF;
        $seasonsData = $this->graphql->query($querySeasons, "Seasons", ["id" => "tt$this->imdbID"]);
        if (!empty($seasonsData->title->episodes)) {
            $bySeason = count($seasonsData->title->episodes->displayableSeasons->edges);
            $byYear = count($seasonsData->title->episodes->displayableYears->edges);
            if ($yearbased == 0) {
                $data = $seasonsData->title->episodes->displayableSeasons->edges;
            } else {
                $data = $seasonsData->title->episodes->displayableYears->edges;
            }
        } else {
            return false;
        }
        return $data;

    }

    #----------------------------------------------------------[ Helper for Episode() GraphQL Get All Episodes ]---
    /**
     * Get all episodes of a title
     * @param string $filter add filter options to query
     * @return array
     */
    protected function graphQlGetAllEpisodes($filter)
    {
        $query = <<<EOF
query Episodes(\$id: ID!, \$after: ID) {
  title(id: \$id) {
    episodes {
      episodes(first: 9999, after: \$after $filter) {
        edges {
          node {
            id
            titleText {
              text
            }
            plot {
              plotText {
                plainText
              }
            }
            primaryImage {
              url
              width
              height
            }
            releaseDate {
              day
              month
              year
            }
            series {
              displayableEpisodeNumber {
                episodeNumber {
                  episodeNumber
                }
              }
            }
          }
        }
        pageInfo {
          endCursor
          hasNextPage
        }
      }
    }
  }
}
EOF;
        // strip spaces from query due to hosters request limit
        $fullQuery = implode("\n", array_map('trim', explode("\n", $query)));
        $endCursor = null;
        $hasNextPage = true;
        $edges = array();
        while ($hasNextPage) {
            $data = $this->graphql->query($fullQuery, "Episodes", ["id" => "tt$this->imdbID", "after" => $endCursor]);
            $edges = array_merge($edges, $data->title->episodes->episodes->edges);
            $hasNextPage = $data->title->episodes->episodes->pageInfo->hasNextPage;
            $endCursor = $data->title->episodes->episodes->pageInfo->endCursor;
        }
        return $edges;
    }

    #----------------------------------------------------------[ Helper for episode() build date string ]---
    /**
     * build date string for episode()
     * @param array $date input date array(['day'], ['month'], ['year'])
     * @return string|null $airDate e.g. '20 jan. 2008'
     */
    private function buildDateString($date)
    {
        $airDate = null;
        if (!empty($date['day'])) {
            $airDate .= $date['day'];
            if (!empty($date['month'])) {
                $airDate .= ' ';
            }
        }
        if (!empty($date['month'])) {
            $airDate .= date('M', mktime(0, 0, 0, $date['month'], 10)) . '.';
            if (!empty($date['year'])) {
                $airDate .= ' ';
            }
        }
        if (!empty($date['year'])) {
            $airDate .= $date['year'];
        }
        return $airDate;
    }

    #----------------------------------------------------------[ Helper for Technical specifications  ]---
    /**
     * Get movie tech specs
     * @param string $type input techspec type like soundMixes or aspectRatios
     * @param string $valueType input type like text or soundMix
     * @param string $arrayName output array name
     * @return array
     */
    protected function techSpec($type, $valueType, $arrayName)
    {
        $query = <<<EOF
query TechSpec(\$id: ID!) {
  title(id: \$id) {
    technicalSpecifications {
      $type {
        items {
          $valueType
          attributes {
            text
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "TechSpec", ["id" => "tt$this->imdbID"]);
        if (!isset($data->title)) {
            return $arrayName;
        }
        if (isset($data->title->technicalSpecifications->$type->items) &&
            is_array($data->title->technicalSpecifications->$type->items) &&
            count($data->title->technicalSpecifications->$type->items) > 0
           )
        {
            foreach ($data->title->technicalSpecifications->$type->items as $item) {
                $attributes = array();
                if (isset($item->attributes) &&
                    is_array($item->attributes) &&
                    count($item->attributes) > 0
                   )
                {
                    foreach ($item->attributes as $attribute) {
                        if (!empty($attribute->text)) {
                            $attributes[] = $attribute->text;
                        }
                    }
                }
                $arrayName[] = array(
                    'type' => isset($item->$valueType) ?
                                    $item->$valueType : null,
                    'attributes' => $attributes
                );
            }
        }
        return $arrayName;
    }

    #----------------------------------------------------------[ Helper GraphQL Get All ]---
    /**
     * Get all date when the data is paginated
     * @param string $queryName The cached query name
     * @param string $fieldName The field on title you want to get
     * @param string $nodeQuery Graphql query that fits inside node { }
     * @param string $filter Add's extra Graphql query filters like categories
     * @return array
     */
    protected function graphQlGetAll($queryName, $fieldName, $nodeQuery, $filter = '')
    {
        $query = <<<EOF
query $queryName(\$id: ID!, \$after: ID) {
  title(id: \$id) {
    $fieldName(first: 9999, after: \$after$filter) {
      edges {
        node {
          $nodeQuery
        }
      }
      pageInfo {
        endCursor
        hasNextPage
      }
    }
  }
}
EOF;
        // strip spaces from query due to hosters request limit
        $fullQuery = implode("\n", array_map('trim', explode("\n", $query)));
        $endCursor = null;
        $hasNextPage = true;
        $edges = array();
        while ($hasNextPage) {
            $data = $this->graphql->query($fullQuery, $queryName, ["id" => "tt$this->imdbID", "after" => $endCursor]);
            if (isset($data->title->{$fieldName})) {
                $edges = array_merge($edges, $data->title->{$fieldName}->edges);
                $hasNextPage = $data->title->{$fieldName}->pageInfo->hasNextPage;
                $endCursor = $data->title->{$fieldName}->pageInfo->endCursor;
            }
        }
        return $edges;
    }

    #----------------------------------------------------------[ imdbID redirect ]---
    /**
     * Check if imdbid is redirected to another id or not.
     * @note It sometimes happens that imdb redirects an existing id to a new id.
     * @note If user uses search class this check isn't nessecary as the returned results already contain a possible new imdbid
     * @return string|false $titleImdbId (the new redirected imdbId) or false (no redirect)
     */
    public function checkRedirect()
    {
        $query = <<<EOF
query Redirect(\$id: ID!) {
  title(id: \$id) {
    meta {
      canonicalId
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Redirect", ["id" => "tt$this->imdbID"]);
        if (isset($data->title->meta->canonicalId) &&
            $data->title->meta->canonicalId != ''
           )
        {
            $titleImdbId = str_replace('tt', '', $data->title->meta->canonicalId);
            if ($titleImdbId  != $this->imdbID) {
                return $titleImdbId;
            } else {
                return false;
            }
        }
        return false;
    }

    #----------------------------------------------------------[ Helper Award() build filter ]---
    /**
     * Build award filter string
     * @param bool $winsOnly true: only won is included, false: all is included
     * @param string $event eventId
     * @return string
     */
    public function awardFilter($winsOnly, $event)
    {
        $filter = ', sort: {by: PRESTIGIOUS, order: DESC}';
        if (!empty($event) || $winsOnly === true) {
            $filter .= ', filter:{';
            if ($winsOnly === true) {
                $filter .= 'wins:WINS_ONLY';
                if (empty($event)) {
                    $filter .= '}';
                } else {
                    $filter .= ', events:"' . trim($event) . '"}';
                }
            } else {
                $filter .= 'events:"' . trim($event) . '"}';
            }
        }
        return $filter;
    }

    #----------------------------------------------------------[ Helper interests() sort by name ]---
    /**
     * Sort by name function for interests()
     * @param array $array input to be sorted
     * @param string $sortOrder "ASC" "DESC"
     * @return array
     */
    public function sortByName($array, $sortOrder)
    {
        if ($sortOrder == 'DESC') {
            // sort array by date
            usort($array, function($a, $b) {
                $ad = $a['displayName'];
                $bd = $b['displayName'];
                if ($ad == $bd) {
                return 0;
                }
                return $ad > $bd ? -1 : 1;
            });
        } else {
            // sort array by date
            usort($array, function($a, $b) {
                $ad = $a['displayName'];
                $bd = $b['displayName'];
                if ($ad == $bd) {
                return 0;
                }
                return $ad < $bd ? -1 : 1;
            });
        }
        return $array;
    }

}
