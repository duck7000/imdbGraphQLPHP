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
 * @copyright (c) 2002-2004 by Giorgos Giagas and (c) 2004-2009 by Itzchak Rehberg and IzzySoft
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
    protected $creditsDirector = array();
    protected $creditsProducer = array();
    protected $creditsWriter = array();
    protected $creditsCinematographer = array();
    protected $languages = array();
    protected $keywords = array();
    protected $mainPoster = null;
    protected $mainPosterThumb = null;
    protected $mainPlotoutline = null;
    protected $mainMovietype = null;
    protected $mainTitle = null;
    protected $mainOriginalTitle = null;
    protected $mainYear = -1;
    protected $mainEndYear = -1;
    protected $mainTop250 = 0;
    protected $mainRating = 0;
    protected $mainRatingVotes = 0;
    protected $mainMetacritics = 0;
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

    #----------------------------------------------------------[ Helper for TitleSearch class ]---
    /**
     * Create an imdb object populated with id, title, year, and movie type
     * @param string $id imdb ID
     * @param string $title film title
     * @param string $orignalTitle Original film title
     * @param int $year
     * @param string $type
     * @param Config $config
     * @param LoggerInterface $logger OPTIONAL override default logger
     * @param CacheInterface $cache OPTIONAL override default cache
     * @return Title
     */
    public static function fromSearchResult(
        $id,
        $title,
        $orignalTitle,
        $year,
        $type,
        Config $config = null,
        LoggerInterface $logger = null,
        CacheInterface $cache = null
    ) {
        $imdb = new Title($id, $config, $logger, $cache);
        $imdb->mainTitle = $title;
        $imdb->mainYear = $year;
        $imdb->mainMovietype = $type;
        return $imdb;
    }

    /**
     * @param string $id IMDb ID. e.g. 285331 for https://www.imdb.com/title/tt0285331/
     * @param Config $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct($id, Config $config = null, LoggerInterface $logger = null, CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->setid($id);
        $this->imageFunctions = new Image();
    }

    #-------------------------------------------------------------[ Title ]---

    /** Get movie type
     * @return string movietype (TV Series, Movie, TV Episode, TV Special, TV Movie, TV Mini Series, Video Game, TV Short, Video)
     * @see IMDB page / (TitlePage)
     * If no movietype has been defined explicitly, it returns 'Movie' -- so this is always set.
     */
    public function movietype()
    {
        if (empty($this->mainMovietype)) {
            $this->titleYear();
            if (empty($this->mainMovietype)) {
                $this->mainMovietype = 'Movie';
            }
        }
        return $this->mainMovietype;
    }

    /** Get movie title
     * @return string title movie title (name)
     * @see IMDB page / (TitlePage)
     */
    public function title()
    {
        if (empty($this->mainTitle)) {
            $this->titleYear();
        }
        return $this->mainTitle;
    }

    /** Get movie original title
     * @return string mainOriginalTitle  movie original title
     * @see IMDB page / (TitlePage)
     */
    public function originalTitle()
    {
        if (empty($this->mainOriginalTitle)) {
            $this->titleYear();
        }
        return $this->mainOriginalTitle ;
    }

    /** Get year
     * @return string year
     * @see IMDB page / (TitlePage)
     */
    public function year()
    {
        if ($this->mainYear == -1) {
            $this->titleYear();
        }
        return $this->mainYear;
    }

    /** Get end-year
     * if production spanned multiple years, usually for series
     * @return int endyear|null
     * @see IMDB page / (TitlePage)
     */
    public function endyear()
    {
        if ($this->mainEndYear == -1) {
            $this->titleYear();
        }
        return $this->mainEndYear;
    }

    #---------------------------------------------------------------[ Runtime ]---
    /**
     * Retrieve all runtimes and their descriptions
     * @return array<array{time: integer, country: string|null, annotations: array()}>
     * time is the length in minutes, country optionally exists for alternate cuts, annotations is an array of comments
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
            foreach ($data->title->runtimes->edges as $edge) {
                $attributes = array();
                if (!empty($edge->node->attributes)) {
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
        return $this->runtimes;
    }

    #----------------------------------------------------------[ Movie Rating ]---
    /**
     * Get movie rating
     * @return int/float or 0
     * @see IMDB page / (TitlePage)
     */
    public function rating()
    {
        if ($this->mainRating == 0) {
            $query = <<<EOF
query Rating(\$id: ID!) {
  title(id: \$id) {
    ratingsSummary {
      aggregateRating
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Rating", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->ratingsSummary->aggregateRating)) {
                $this->mainRating = $data->title->ratingsSummary->aggregateRating;
            }
        }
        return $this->mainRating;
    }

     /**
     * Return number of votes for this movie
     * @return int
     * @see IMDB page / (TitlePage)
     */
    public function votes()
    {
        if ($this->mainRatingVotes == 0) {
            $query = <<<EOF
query RatingVotes(\$id: ID!) {
  title(id: \$id) {
    ratingsSummary {
      voteCount
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "RatingVotes", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->ratingsSummary->voteCount)) {
                $this->mainRatingVotes = $data->title->ratingsSummary->voteCount;
            }
        }
        return $this->mainRatingVotes;
    }

    /**
     * Rating out of 100 on metacritic
     * @return int
     */
    public function metacritic()
    {
        if ($this->mainMetacritics == 0) {
            $query = <<<EOF
query Metacritic(\$id: ID!) {
  title(id: \$id) {
    metacritic {
      metascore {
        score
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Metacritic", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->metacritic->metascore->score)) {
               $this->mainMetacritics = $data->title->metacritic->metascore->score;
            }
        }
        return $this->mainMetacritics;
    }

    #----------------------------------------------------------[ Popularity ]---
    /**
     * Get movie popularity rank
     * @return array(currentRank: int, changeDirection: string, difference: int)
     * @see IMDB page / (TitlePage)
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
     * @param $spoil boolean (true or false) to include spoilers or not, isSpoiler indicates if this question is spoiler or not
     * @return array of array(question: string, answer: string, isSpoiler: boolean)
     * @see IMDB page / (Faq)
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
        return $this->faqs;
    }

    #-------------------------------------------------------[ Recommendations ]---

    /**
     * Get recommended movies (People who liked this...also liked)
     * @return array<array{title: string, imdbid: string, rating: int, img: string, year: int}>
     * @see IMDB page / (TitlePage)
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
        return $this->recommendations;
    }

    #--------------------------------------------------------[ Language Stuff ]---
    /** Get all spoken languages spoken in this title
     * @return array languages (array[0..n] of strings)
     * @see IMDB page / (TitlePage)
     */
    public function language()
    {
        if (empty($this->languages)) {
            $query = <<<EOF
query Languages(\$id: ID!) {
  title(id: \$id) {
    spokenLanguages {
      spokenLanguages {
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Languages", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->spokenLanguages->spokenLanguages)) {
                foreach ($data->title->spokenLanguages->spokenLanguages as $language) {
                    if (!empty($language->text)) {
                        $this->languages[] = $language->text;
                    }
                }
            }
            return $this->languages;
        }
    }

    #--------------------------------------------------------------[ Genre(s) ]---
    /** Get all genres the movie is registered for
     * @return array genres (array[0..n] of mainGenre| string, subGenre| array())
     * @see IMDB page / (TitlePage)
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
            if (!empty($data->title->titleGenres->genres)) {
                foreach ($data->title->titleGenres->genres as $edge) {
                    $subGenres = array();
                    if (!empty($edge->subGenres)) {
                        foreach ($edge->subGenres as $subGenre) {
                            $subGenres[] = isset($subGenre->keyword->text->text) ?
                                                 ucwords($subGenre->keyword->text->text) : null;
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

    #--------------------------------------------------------[ Plot (Outline) ]---
    /** Get the main Plot outline for the movie as displayed on top of title page
     * @return string plotoutline
     * @see IMDB page / (TitlePage)
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

    #--------------------------------------------------------[ Photo specific ]---
    /**
     * Get the main photo image url for thumbnail or full size
     * @param boolean $thumb get the thumbnail (height: 281) or large (max 1000 pixels)
     * @return string|false photo (string URL if found, FALSE otherwise)
     * @see IMDB page / (TitlePage)
     */
    public function photo($thumb = true)
    {
        if (empty($this->mainPoster)) {
            $this->populatePoster();
        }
        if (!$thumb && empty($this->mainPoster)) {
            return false;
        }
        if ($thumb && empty($this->mainPosterThumb)) {
            return false;
        }
        if ($thumb) {
            return $this->mainPosterThumb;
        }
        return $this->mainPoster;
    }

    /**
     * Save the poster/cover image to disk
     * @param string $path where to store the file
     * @param boolean $thumb get the thumbnail or the
     *        bigger variant (max width 1000 pixels - FALSE)
     * @return boolean success
     * @see IMDB page / (TitlePage)
     */
    public function savephoto($path, $thumb = true)
    {
        $photoUrl = $this->photo($thumb);
        if (!$photoUrl) {
            return false;
        }

        $req = new Request($photoUrl, $this->config);
        $req->sendRequest();
        if (strpos($req->getResponseHeader("Content-Type"), 'image/jpeg') === 0 ||
            strpos($req->getResponseHeader("Content-Type"), 'image/gif') === 0 ||
            strpos($req->getResponseHeader("Content-Type"), 'image/bmp') === 0) {
            $image = $req->getResponseBody();
        } else {
            $ctype = $req->getResponseHeader("Content-Type");
            $this->debug_scalar("*photoerror* at " . __FILE__ . " line " . __LINE__ . ": " . $photo_url . ": Content Type is '$ctype'");
            if (substr($ctype, 0, 4) == 'text') {
                $this->debug_scalar("Details: <PRE>" . $req->getResponseBody() . "</PRE>\n");
            }
            return false;
        }

        $fp2 = fopen($path, "w");
        if (!$fp2) {
            $this->logger->warning("Failed to open [$path] for writing  at " . __FILE__ . " line " . __LINE__ . "...<BR>");
            return false;
        }
        fputs($fp2, $image);
        return true;
    }

    /** Get the URL for the movies cover image
     * @param boolean $thumb get the thumbnail (default) or the
     *        bigger variant (max width 1000 pixels - FALSE)
     * @return mixed url (string URL or FALSE if none)
     * @see IMDB page / (TitlePage)
     */
    public function photoLocalurl($thumb = true)
    {
        if ($thumb) {
            $ext = "";
        } else {
            $ext = "_big";
        }
        if (!is_dir($this->config->photoroot)) {
            $this->debug_scalar("<BR>***ERROR*** The configured image directory does not exist!<BR>");
            return false;
        }
        $path = $this->config->photoroot . "tt{$this->imdbid()}" . "{$ext}.jpg";
        if (file_exists($path)) {
            return $this->config->photodir . "tt{$this->imdbid()}" . "{$ext}.jpg";
        }
        if (!is_writable($this->config->photoroot)) {
            $this->debug_scalar("<BR>***ERROR*** The configured image directory lacks write permission!<BR>");
            return false;
        }
        if ($this->savephoto($path, $thumb)) {
            return $this->config->photodir . "tt{$this->imdbid()}" . "{$ext}.jpg";
        }
        return false;
    }

    #-------------------------------------------------[ Country of Origin ]---
    /**
     * Get country of origin
     * @return array country (array[0..n] of string)
     * @see IMDB page / (TitlePage)
     */
    public function country()
    {
        if (empty($this->countries)) {
            $query = <<<EOF
query Countries(\$id: ID!) {
  title(id: \$id) {
    countriesOfOrigin {
      countries {
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Countries", ["id" => "tt$this->imdbID"]);
            if (!empty($data->title->countriesOfOrigin->countries)) {
                foreach ($data->title->countriesOfOrigin->countries as $country) {
                    if (!empty($country->text)) {
                        $this->countries[] = $country->text;
                    }
                }
            }
        }
        return $this->countries;
    }

    #-------------------------------------------------[ Release dates ]---
    /**
     * Get all release dates for this title
     * @return releaseDates array[0..n] of array[country, day, month, year, array attributes]
     * @see IMDB page / (TitlePage)
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
            if (!empty($data)) {
                foreach ($data as $edge) {
                    $attributes = array();
                    if (!empty($edge->node->attributes)) {
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

    #------------------------------------------------------------[ Movie AKAs ]---
    /**
     * Get movie's alternative names
     * The first item in the list will be the original title
     * @return array<array{title: string, country: string, countryId: string, language: string, languageId: string, comment: array()}>
     * Ordered Ascending by Country
     * @see IMDB page ReleaseInfo
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
            $originalTitle = $this->originalTitle();
            if (!empty($originalTitle)) {
                $this->akas[] = array(
                    'title' => ucwords($originalTitle),
                    'country' => "(Original Title)",
                    'countryId' => null,
                    'language' => null,
                    'languageId' => null,
                    'comment' => array()
                );
            }
            foreach ($data as $edge) {
                $comments = array();
                if (!empty($edge->node->attributes)) {
                    foreach ($edge->node->attributes as $attribute) {
                        if (!empty($attribute->text)) {
                            $comments[] = $attribute->text;
                        }
                    }
                }
                $this->akas[] = array(
                    'title' => isset($edge->node->text) ?
                                     ucwords($edge->node->text) : null,
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
        return $this->akas;
    }

    #-------------------------------------------------------[ MPAA / PG / FSK ]---
    /**
     * Get the MPAA rating / Parental Guidance / Age rating for this title by country
     * @return array array[0..n] of array[country,rating,comment of array()] comment whithout brackets
     * @see IMDB Parental Guidance page / (parentalguide)
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
            foreach ($data as $edge) {
                $comments = array();
                if (!empty($edge->node->attributes)) {
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
        return $this->mpaas;
    }

    #-------------------------------------------------------[ ParentsGuide ]---
    /** Info for parents like Violence, Drugs. Alcohol etc
     * @param $spoil boolean if true spoilers are also included.
     * @return array categorized array of array()
     *  [nudity] => Array
     *      [severity] =>           (string) None (like mild,severe,none etc)
     *      [severityVotedFor] =>   (int) 34 (how many people voted for this severity)
     *      [totalSeverityVotes] => (int) 64 (total amount of voters)
     *      [guideItems] => Array()
     *          [0] => Array()
     *              [isSpoiler] => (boolean) (indicates if entry is a spoiler or not)
     *              [guideText] => (string) A couple in swimwear are seen lying in a sexualised pose together.
     * @see IMDB page /parentsguide
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
        foreach ($data->title->parentsGuide->categories as $category) {
            $guideItems = array();
            if (!empty($category->guideItems->edges)) {
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
        return $this->parentsGuide;
    }

    #----------------------------------------------[ Position in the "Top250" ]---
    /**
     * Find the position of a movie or tv show in the top 250 ranked movies or tv shows
     * @return int position a number between 1..250 if ranked, 0 otherwise
     * @author Ed
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

    #=====================================================[ /plotsummary page ]===
    /** Get movie plots without Spoilers
     * @param $spoil boolean if true spoilers are also included, default: false.
     * @return array array[0..n] string plot, string author]
     * @see IMDB page /plotsummary
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
        return $this->plot;
    }

    #========================================================[ /taglines page ]===
    /**
     * Get all available taglines for the movie
     * @return string[] taglines
     * @see IMDB page /taglines
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
            foreach ($data->title->taglines->edges as $edge) {
                if (!empty($edge->node->text)) {
                    $this->taglines[] = $edge->node->text;
                }
            }
        }
        return $this->taglines;
    }

    #=====================================================[ /fullcredits page ]===
    #----------------------------------------------------------------[ PrincipalCredits ]---
    /*
    * Get the PrincipalCredits for this title (limited to 3 items per category)
    * @return array creditsPrincipal[category][Director, Writer, Creator, Stars] (array[0..n] of array[name,imdbid])
    * Not all categories are always available
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
            foreach ($data->title->principalCredits as $value){
                $category = 'Unknown';
                $credits = array();
                if (!empty($value->credits[0]->category->text)) {
                    $category = $value->credits[0]->category->text;
                    if ($category == "Actor" || $category == "Actress") {
                        $category = "Star";
                    }
                }
                if (!empty($value->credits)) {
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
        return $this->creditsPrincipal;
    }

    #----------------------------------------------------------------[ Actors]---
    /*
     * Get the actors/cast members for this title
     * @return array cast (array[0..n] of array[imdb,name,name_alias,credited,array character,array comments,thumb])
     * e.g.
     * <pre>
     * array (
     *  'imdb' => '0922035',
     *  'name' => 'Dominic West', // Actor's name on imdb,
     *  'name_alias' => alias name (as D west),
     *  'credited' => true\false False if stated (uncredited),
     *  'character' => array "Det. James 'Jimmy' McNulty",
     *  'comment' => array comments like archive voice etc,
     *  'thumb' => 'https://ia.media-imdb.com/images/M/MV5BMTY5NjQwNDY2OV5BMl5BanBnXkFtZTcwMjI2ODQ1MQ@@._V1_SY44_CR0,0,32,44_AL_.jpg',
     * )
     * </pre>
     * @see IMDB page /fullcredits
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
            foreach ($data as $edge) {
                $castCharacters = array();
                if (!empty($edge->node->characters)) {
                    foreach ($edge->node->characters as $character) {
                        if (!empty($character->name)) {
                            $castCharacters[] = $character->name;
                        }
                    }
                }
                $comments = array();
                $nameAlias = null;
                $credited = true;
                if (!empty($edge->node->attributes)) {
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
            return $this->creditsCast;
        }
    }

    #-------------------------------------------------------------[ Directors ]---
    /**
     * Get the director(s) of the movie
     * @return array director (array[0..n] of arrays[imdb,name,jobs[],attributes[],episode array(total, year, endYear)])
     * @see IMDB page /fullcredits
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
     * Get the cinematographer of the title
     * @return array creditsCinematographer (array[0..n] of arrays[imdb,name,jobs[],attributes[],episode array(total, year, endYear)])
     * @see IMDB page /fullcredits
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
    /** Get the writer(s)
     * @return array writers (array[0..n] of arrays[imdb,name,jobs[],attributes[],episode array(total, year, endYear)])
     * @see IMDB page /fullcredits
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
    /** Obtain the producer credits of this title
     * @return array (array[0..n] of arrays[imdb,name,jobs[],attributes[],episode array(total, year, endYear)])
     * @see IMDB page /fullcredits
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
    /** Obtain the composer(s) ("Original Music by...")
     * @return array composer (array[0..n] of arrays[imdb,name,jobs[],attributes[],episode array(total, year, endYear)])
     * @see IMDB page /fullcredits
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
    /** Obtain the stunts credits of this title
     * @return array (array[0..n] of arrays[imdb,name,jobs[],attributes[],episode array(total, year, endYear)])
     * @see IMDB page /fullcredits
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
    /** Obtain thanks credits of this title
     * @return array (array[0..n] of arrays[imdb,name,jobs[],attributes[],episode array(total, year, endYear)])
     * @see IMDB page /fullcredits
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
    /** Obtain Visual Effects credits of this title
     * @return array (array[0..n] of arrays[imdb,name,jobs[],attributes[],episode array(total, year, endYear)])
     * @see IMDB page /fullcredits
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
    /** Obtain Special Effects credits of this title
     * @return array (array[0..n] of arrays[imdb,name,jobs[],attributes[],episode array(total, year, endYear)])
     * @see IMDB page /fullcredits
     */
    public function specialEffects()
    {
        if (!empty($this->creditsSpecialEffects)) {
            return $this->creditsSpecialEffects;
        }
        $specialEffectsData = $this->creditHelper("special_effects");
        return $this->creditsSpecialEffects = $specialEffectsData;
    }

    #====================================================[ /crazycredits page ]===
    #----------------------------------------------------[ CrazyCredits Array ]---
    /**
     * Get the Crazy Credits
     * @return string[]
     * @see IMDB page /crazycredits
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
            foreach ($data as $edge) {
                if (!empty($edge->node->text->plainText)) {
                    $this->crazyCredits[] = preg_replace('/\s\s+/', ' ', $edge->node->text->plainText);
                }
            }
        }
        return $this->crazyCredits;
    }

    #========================================================[ /episodes page ]===
    #--------------------------------------------------------[ Episodes Array ]---
    /**
     * Get the series episode(s)
     * @return array episodes
     * array(1) {
     *   [1]=>
     *   array(13) {
     *       [1]=>          //can be seasonnumber, year or -1 (Unknown)
     *       array(6) {
     *       ["imdbid"]=>   string "1495166"
     *       ["title"]=>    string "Pilot"
     *       ["airdate"]=>  string "7 jun. 2010"
     *       [airdateParts] => Array
     *                   (
     *                       [day] =>   int 7
     *                       [month] => int 6
     *                       [year] =>  int 2010
     *                   )
     *       ["plot"]=>     string
     *       ["episode"]=>  string //can be episodenumber or -1 (Unknown)
     *       ["imgUrl"]=>   string
     *       }
     *   }
     * @see IMDB page /episodes
     * @param $thumb boolean true: thumbnail (cropped from center 224x126), false: large (max width 1000 pixels)
     * @param $yearbased This gives user control if returned episodes are yearbased or season based
     * @version The outer array keys reflects the real season seasonnumber! Episodes can start at 0 (pilot episode)
     */
    public function episode($thumb = true, $yearbased = 0)
    {
        if ($this->movietype() === "TV Series" || $this->movietype() === "TV Mini Series") {
            if (empty($this->seasonEpisodes)) {
                // Check if season or year based
                $seasonsData = $this->seasonYearCheck($yearbased);
                if ($seasonsData == null) {
                    return $this->seasonEpisodes;
                }
                foreach ($seasonsData as $edge) {
                    if (empty($edge->node->text)) {
                        return $this->seasonEpisodes;
                    }
                    $seasonYear = $edge->node->text;
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
     * Boolean flag if this is a still running tv series or ended
     * @return boolean: false if ended, true if still running or null (not a tv series)
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

    #===========================================================[ /goofs page ]===
    #-----------------------------------------------------------[ Goofs Array ]---
    /** Get the goofs
     * @param $spoil boolean if true spoilers are also included.
     * @return array goofs (array[categoryId] of array[content, isSpoiler]
     * @see IMDB page /goofs
     */
    public function goof($spoil = false)
    {
        // imdb connection category ids to camelCase
        $categoryIds = array(
            'continuity' => 'continuity',
            'factual_error' => 'factualError',
            'not_a_goof' => 'notAGoof',
            'revealing_mistake' => 'revealingMistake',
            'miscellaneous' => 'miscellaneous',
            'anachronism' => 'anachronism',
            'audio_visual_unsynchronized' => 'audioVisualUnsynchronized',
            'crew_or_equipment_visible' => 'crewOrEquipmentVisible',
            'error_in_geography' => 'errorInGeography',
            'plot_hole' => 'plotHole',
            'boom_mic_visible' => 'boomMicVisible',
            'character_error' => 'characterError'
        );

        if (empty($this->goofs)) {
            foreach ($categoryIds as $categoryId) {
                $this->goofs[$categoryId] = array();
            }
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
            foreach ($data as $edge) {
                $this->goofs[$categoryIds[$edge->node->category->id]][] = array(
                    'content' => isset($edge->node->displayableArticle->body->plainText) ?
                                       $edge->node->displayableArticle->body->plainText : null,
                    'isSpoiler' => $edge->node->isSpoiler
                );
            }
        }
        return $this->goofs;
    }

    #==========================================================[ /quotes page ]===
    /** Get the quotes for a given movie
     * @return array quote array[string quote];
     * @see IMDB page /quotes
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
        return $this->quotes;
    }

    #==========================================================[ /trivia page ]===
    /**
     * Get the trivia info
     * @param boolean $spoil if true spoilers are also included.
     * @return array (array[categoryId] of array[content, names: array(name, id), trademark, isSpoiler]
     * @see IMDB page /trivia
     */
    public function trivia($spoil = false)
    {
        // imdb connection category ids to camelCase
        $categoryIds = array(
            'uncategorized' => 'uncategorized',
            'actor-trademark' => 'actorTrademark',
            'cameo' => 'cameo',
            'director-cameo' => 'directorCameo',
            'director-trademark' => 'directorTrademark',
            'smithee' => 'smithee'
        );

        if (empty($this->trivias)) {
            foreach ($categoryIds as $categoryId) {
                $this->trivias[$categoryId] = array();
            }

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
            foreach ($data as $edge) {
                $names = array();
                if (!empty($edge->node->relatedNames)) {
                    foreach ($edge->node->relatedNames as $name) {
                        $names[] = array(
                            'name' => isset($name->nameText->text) ?
                                            $name->nameText->text : null,
                            'id' => isset($name->id) ?
                                          str_replace('nm', '', $name->id) : null
                        );
                    }
                }
                $this->trivias[$categoryIds[$edge->node->category->id]][] = array(
                    'content' => isset($edge->node->displayableArticle->body->plainText) ?
                                       preg_replace('/\s\s+/', ' ', $edge->node->displayableArticle->body->plainText) : null,
                    'names' => $names,
                    'trademark' => isset($edge->node->trademark->plainText) ?
                                         $edge->node->trademark->plainText : null,
                    'isSpoiler' => $edge->node->isSpoiler
                );
            }
        }
        return $this->trivias;
    }

    #======================================================[ Soundtrack ]===
    /**
     * Get the soundtrack listing
     * @return array soundtracks
     * [credits]: all credit lines as text, each in one element
     * [creditSplit]: credits split by type, name, nameId and attribute
     * [comments]: if not a credited person, it is considered comment as plain text
     * Array()
     *      [0] => Array()
     *          [soundtrack] => (string) Dangerous
     *          [credits] => Array()
     *              [0] => (string) Performed by The Doobie Brothers
     *              [1] => (string) Written by Patrick Simmons (as Pat Simmons)
     *              [2] => (string) Published by Soquel Songs and ASCAP
     *              [3] => (string) Courtesy of Capitol Records
     *          [creditSplit] => Array()
     *              [creditors] => Array()
     *                  [0] => Array()
     *                      [creditType] => (string) Written by
     *                      [name] =>       (string) Patrick Simmons
     *                      [nameId] =>     (string) 2003944
     *                      [attribute] =>  (string) as Pat Simmons
     *              [comment] => Array()
     *                  [0] => (string) Courtesy of Capitol Records 
     * @see IMDB page /soundtrack
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
            foreach ($data as $edge) {
                $title = 'Unknown';
                if (!empty($edge->node->text)) {
                    $title = trim($edge->node->text);
                }
                $credits = array();
                $creditComments = array();
                $crediters = array();
                if (!empty($edge->node->comments)) {
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
        return $this->soundtracks;
    }

    #=======================================================[ /locations page ]===
    /**
     * Filming locations
     * @return array locations (array[0..n] of arrays[real,array movie])
     * real: Real filming location, movie: location in the movie
     * @see IMDB page /locations
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
            foreach ($data as $edge) {
                $movie = array();
                if (!empty($edge->node->displayableProperty->qualifiersInMarkdownList)) {
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
        return $this->locations;
    }

    #==================================================[ /companycredits page ]===

    #---------------------------------------------------[ Producing Companies ]---

    /** Info about Production Companies
     * @return array<array{name: string, id: string, country: string, attribute: string, year: int}>
     * @see IMDB page /companycredits
     */
    public function prodCompany()
    {
        if (empty($this->compCreditsProd)) {
            $this->compCreditsProd = $this->companyCredits("production");
        }
        return $this->compCreditsProd;
    }

    #------------------------------------------------[ Distributing Companies ]---

    /** Info about distributors
     * @return array<array{name: string, id: string, country: string, attribute: string, year: int}>
     * @see IMDB page /companycredits
     */
    public function distCompany()
    {
        if (empty($this->compCreditsDist)) {
            $this->compCreditsDist = $this->companyCredits("distribution");
        }
        return $this->compCreditsDist;
    }

    #---------------------------------------------[ Special Effects Companies ]---

    /** Info about Special Effects companies
     * @return array<array{name: string, id: string, country: string, attribute: string, year: int}>
     * @see IMDB page /companycredits
     */
    public function specialCompany()
    {
        if (empty($this->compCreditsSpecial)) {
            $this->compCreditsSpecial = $this->companyCredits("specialEffects");
        }
        return $this->compCreditsSpecial;
    }

    #-------------------------------------------------------[ Other Companies ]---

    /** Info about other companies
     * @return array<array{name: string, id: string, country: string, attribute: string, year: int}>
     * @see IMDB page /companycredits
     */
    public function otherCompany()
    {
        if (empty($this->compCreditsOther)) {
            $this->compCreditsOther = $this->companyCredits("miscellaneous");
        }
        return $this->compCreditsOther;
    }

    #-------------------------------------------------------[ Connections ]---
    /** Info about connections or references with other titles
     * @return array of array('titleId: string, 'titleName: string, titleType: string, year: int, endYear: int, seriesName: string, description: string)
     * @see IMDB page /companycredits
     */
    public function connection()
    {
        // imdb connection category ids to camelCase
        $categoryIds = array(
            'alternate_language_version_of' => 'alternateLanguageVersionOf',
            'edited_from' => 'editedFrom',
            'edited_into' => 'editedInto',
            'featured_in' => 'featured',
            'features' => 'features',
            'followed_by' => 'followedBy',
            'follows' => 'follows',
            'referenced_in' => 'referenced',
            'references' => 'references',
            'remade_as' => 'remadeAs',
            'remake_of' => 'remakeOf',
            'same_franchise' => 'sameFranchise',
            'spin_off' => 'spinOff',
            'spin_off_from' => 'spinOffFrom',
            'spoofed_in' => 'spoofed',
            'spoofs' => 'spoofs',
            'version_of' => 'versionOf'
        );

        if (empty($this->connections)) {
            foreach ($categoryIds as $categoryId) {
                $this->connections[$categoryId] = array();
            }
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
            foreach ($edges as $edge) {
                $this->connections[$categoryIds[$edge->node->category->id]][] = array(
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
        return $this->connections;
    }

    #-------------------------------------------------------[ External sites ]---
    /** external websites with info of this title, excluding external reviews.
     * @return array of array('label: string, 'url: string, language: array[])
     * @see IMDB page /externalsites
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
            foreach ($edges as $edge) {
                $language = array();
                if (!empty($edge->node->externalLinkLanguages)) {
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
        return $this->externalSites;
    }

    #========================================================[ /Box Office page ]===
    #-------------------------------------------------------[ productionBudget ]---
    /** Info about productionBudget
     * @return productionBudget: array[amount, currency]>
     * @see IMDB page /title
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

    #-------------------------------------------------------[ rankedLifetimeGrosses ]---
    /** Info about Grosses, ranked by amount
     * @return array[] array[areatype: string, amount: int, currency: string]>
     * @see IMDB page /title
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
        return $this->grosses;
    }

    #========================================================[ /keywords page ]===
    /**
     * Get all keywords from movie
     * @return array keywords
     * @see IMDB page /keywords
     */
    public function keyword()
    {
        if (empty($this->keywords)) {
            $query = <<<EOF
keyword {
  text {
    text
  }
}
EOF;
            $data = $this->graphQlGetAll("Keywords", "keywords", $query);
            foreach ($data as $edge) {
                if (!empty($edge->node->keyword->text->text)) {
                    $this->keywords[] = $edge->node->keyword->text->text;
                }
            }
        }
        return $this->keywords;
    }

    #========================================================[ /Alternate versions page ]===
    /**
     * Get the Alternate Versions for a given movie
     * @return array Alternate Version (array[0..n] of string)
     * @see IMDB page /alternateversions
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
            foreach ($data as $edge) {
                if (!empty($edge->node->text->plainText)) {
                    $this->alternateversions[] = $edge->node->text->plainText;
                }
            }
        }
        return $this->alternateversions;
    }

    #-------------------------------------------------[ Main images ]---
    /**
     * Get image URLs for (default 6) pictures from photo page
     * @param $amount, int for how many images, max = 9999
     * @param $thumb boolean
     *      true: height is always the same (set in config), width is variable!
     *      false: untouched max width 1000 pixels
     * @return array [0..n] of string image source
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
        return $this->mainPhoto;
    }

    #-------------------------------------------------[ Trailer ]---
    /**
     * Get video URL's and images from videogallery page (Trailers only)
     * @param $amount (int)determine how many trailers are returned, default: 1
     * @param $customThumb boolean default: true
     *      true: old style imdb custom thumb (fixed 200x150)
     *      false: new style (fixed 500x281)
     * @return array()
     *      [0] array()
     *          [id] =>             (string) (without vi) 4030506521
     *          [name] =>           (string) (name of trailer) e.g. A Clockwork Orange
     *          [runtime] =>        (int)    (in seconds!) 130 
     *          [description] =>    (string) (description text of this trailer)
     *          [titleName] =>      (string) (name of Title) e.g. A Clockwork Orange
     *          [titleYear] =>      (int)    1971
     *          [playbackUrl] =>    (string) https://www.imdb.com/video/vi4030506521/
     *          [imageUrl] =>       (string) (affected by $customThumb)
     * videoUrl is an embeded url that is tested to work in iframe (won't work in html5 <video>)
     */
    public function trailer($amount = 1, $customThumb = true)
    {
        if (empty($this->trailers)) {
            $query = <<<EOF
query Video(\$id: ID!) {
  title(id: \$id) {
    primaryVideos(first: 9999) {
      edges {
        node {
          id
          name {
            value
          }
          thumbnail {
            url
            width
            height
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
            foreach ($data->title->primaryVideos->edges as $edge) {
                // check contentType is set and contentType == Trailer
                if (isset($edge->node->contentType->displayName->value) && $edge->node->contentType->displayName->value !== "Trailer") {
                    continue;
                }
                // Embed URL
                $embedUrl = null;
                if (!empty($edge->node->id)) {
                    $embedUrl = "https://www.imdb.com/video/imdb/" . $edge->node->id . "/imdb/embed";
                    // Check if embed URL not == 404 or 401
                    $headers = @get_headers($embedUrl);
                    if (empty($headers) || substr($headers[0], 9, 3) == "404" || substr($headers[0], 9, 3) == "401") {
                        continue;
                    }
                }
                $titleName = isset($edge->node->primaryTitle->titleText->text) ?
                                   $edge->node->primaryTitle->titleText->text : null;
                $runtime = isset($edge->node->runtime->value) ?
                                 $edge->node->runtime->value : null;
                $thumbnailUrl = isset($edge->node->thumbnail->url) ?
                                      $edge->node->thumbnail->url : null;
                $thumbUrl = '';
                if (!empty($thumbnailUrl)) {
                    //CustomThumb
                    if ($customThumb !== false) {
                        $timeString = '';
                        if (!empty($runtime)) {
                            $timeString .= sprintf("%02d", ($runtime / 60)) . '%253A' . sprintf("%02d", $runtime % 60);
                        }
                        $title = '';
                        if (!empty($titleName)) {
                            $title = rawurlencode(rawurlencode($titleName));
                        }
                        $thumbUrl = str_replace('.jpg', '', $thumbnailUrl);
                        $thumbUrl .= '1_SP330,330,0,C,0,0,0_CR65,90,200,150_'
                                     . 'PIimdb-blackband-204-14,TopLeft,0,0_'
                                     . 'PIimdb-blackband-204-28,BottomLeft,0,1_CR0,0,200,150_'
                                     . 'PIimdb-bluebutton-big,BottomRight,-1,-1_'
                                     . 'ZATrailer,4,123,16,196,verdenab,8,255,255,255,1_'
                                     . 'ZAon%2520IMDb,4,1,14,196,verdenab,7,255,255,255,1_'
                                     . 'ZA' . $timeString .',164,1,14,36,verdenab,7,255,255,255,1_'
                                     . 'ZA' . $title . ',4,138,14,176,arialbd,7,255,255,255,1_.jpg';
                    } else {
                        // New style thumb
                        if (!empty($thumbnailUrl)) {
                            $fullImageWidth = $edge->node->thumbnail->width;
                            $fullImageHeight = $edge->node->thumbnail->height;
                            $img = str_replace('.jpg', '', $thumbnailUrl);
                            $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, 500, 281);
                            $thumbUrl = $img . $parameter;
                        }
                    }
                }
                if (count($this->trailers) < $amount) {
                    $this->trailers[] = array(
                        'name' => isset($edge->node->name->value) ?
                                        $edge->node->name->value : null,
                        'runtime' => $runtime,
                        'description' => isset($edge->node->description->value) ?
                                               $edge->node->description->value : null,
                        'titleName' => $titleName,
                        'titleYear' => isset($edge->node->primaryTitle->releaseYear->year) ?
                                             $edge->node->primaryTitle->releaseYear->year : null,
                        'videoUrl' => $embedUrl,
                        'videoImageUrl' => $thumbUrl
                    );
                }
            }
        }
        return $this->trailers;
    }

    #-------------------------------------------------[ Video ]---
    /**
     * Get all video URL's and images from videogallery page
     * @return array categorized by type array videos
     *     [Trailer] => Array
     *          [0] => Array()
     *              [id] => 4030506521
     *              [name] => A Clockwork Orange
     *              [runtime] => 130
     *              [description] => Trailer for A Clockwork Orange - Two-Disc Anniversary Edition Blu-ray Book Packaging
     *              [titleName] => A Clockwork Orange
     *              [titleYear] => 1971
     *              [playbackUrl] => https://www.imdb.com/video/vi4030506521/
     *              [imageUrl] => https://m.media-amazon.com/images/M/MVTg@._V1_QL75_UX500_CR0,47,500,281_.jpg
     *      [Clip] => Array()
     *          [0] => Array()
     *              [id] => 815316505
     *              [name] => 'The Platform' & Future Films From the IMDb Top 250
     *              [runtime] => 244
     *              [description] => On this IMDbrief, we break down our favorite movies from the IMDb Top 250 that boldly look to what might lie ahead.
     *              [titleName] => 'The Platform' & Future Films From the IMDb Top 250
     *              [titleYear] => 2020
     *              [playbackUrl] => https://www.imdb.com/video/vi815316505/
     *              [imageUrl] => https://m.media-amazon.com/images/M/MV5BMW8@._V1_QL75_UX500_CR0,0,500,281_.jpg
     */
    public function video()
    {
        if (empty($this->videos)) {
            $query = <<<EOF
query Video(\$id: ID!) {
  title(id: \$id) {
    videoStrip(first:9999) {
      edges {
        node {
          id
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
            foreach ($data->title->videoStrip->edges as $edge) {
                $thumbUrl = null;
                $videoId = isset($edge->node->id) ?
                                 str_replace('vi', '', $edge->node->id) : null;
                if (!empty($edge->node->thumbnail->url)) {
                    $fullImageWidth = $edge->node->thumbnail->width;
                    $fullImageHeight = $edge->node->thumbnail->height;
                    $img = str_replace('.jpg', '', $edge->node->thumbnail->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, 500, 281);
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
                    'imageUrl' => $thumbUrl
                );
            }
        }
        return $this->videos;
    }

    #-------------------------------------------------------[ Main Awards ]---
    /**
     * Get main awards (not including total wins and total nominations)
     * @return array mainAwards (array[award|string, nominations|int, wins|int])
     * @see IMDB page / (TitlePage)
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
     * @param $winsOnly Default: false, set to true to only get won awards
     * @param $event Default: "" fill eventId Example "ev0000003" to only get Oscars
     *  Possible values for $event:
     *  ev0000003 (Oscar)
     *  ev0000223 (Emmy)
     *  ev0000292 (Golden Globe)
     *  ev0000471 (National Society of Film Critics Awards, USA)
     *  ev0000004 (Academy of Science Fiction, Fantasy & Horror Films, USA)
     *  ev0000123 (BAFTA Awards)
     *  ev0000296 (Satellite Awards)
     *  ev0000453 (MTV Movie + TV Awards)
     *  ev0000511 (Online Film Critics Society Awards)
     *  ev0000786 (Empire Awards, UK)
     *  ev0001644 (DVD Exclusive Awards)
     *  ev0002704 (Online Film & Television Association)
     *  ev0002990 (Awards Circuit Community Awards)
     *  ev0003023 (Golden Schmoes Awards)
     *  ev0000133 (Critics Choice Awards)
     *  ev0000403 (London Critics Circle Film Awards)
     *  ev0000530 (People's Choice Awards, USA)
     * @return array[festivalName][0..n] of 
     *      array[awardYear,awardWinner(bool),awardCategory,awardName,awardNotes
     *      array awardPerons[creditId,creditName,creditNote],awardOutcome] array total(win, nom)
     *  Array
     *       (
     *           [Academy Awards, USA] => Array
     *               (
     *                   [0] => Array
     *                   (
     *                   [awardYear] => 1972
     *                   [awardWinner] => 
     *                   [awardCategory] => Best Picture
     *                   [awardName] => Oscar
     *                   [awardPerons] => Array
     *                       (
     *                           [0] => Array
     *                               (
     *                                   [creditId] => 0000040
     *                                   [creditName] => Stanley Kubrick
     *                                   [creditNote] => screenplay/director
     *                                   [nameFullImageUrl] => (string) max width 1000 pixels
     *                                   [nameThumbImageUrl] => string 140x207 pixels fixed
     *                               )
     *
     *                       )
     *                   [awardNotes] => Based on the novel
     *                   [awardOutcome] => Nominee
     *                   )
     *               )
     *           )
     *           [total] => Array
     *           (
     *               [win] => 12
     *               [nom] => 26
     *           )
     *
     *       )
     * @see IMDB page / (TitlePage)
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
            foreach ($data as $edge) {
                $eventName = isset($edge->node->award->event->text) ?
                                   $edge->node->award->event->text : null;
                $awardIsWinner = $edge->node->isWinner;
                $conclusion = $awardIsWinner === true ? "Winner" : "Nominee";
                $awardIsWinner === true ? $winnerCount++ : $nomineeCount++;
                //credited persons
                $names = array();
                if (!empty($edge->node->awardedEntities->secondaryAwardNames)) {
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
            if ($winnerCount > 0 || $nomineeCount > 0) {
                $this->awards['total'] = array(
                    'win' => $winnerCount,
                    'nom' => $nomineeCount
                );
            }
        }
        return $this->awards;
    }

    #----------------------------------------------------------[ Sound mix ]---
    /**
     * Get movie sound mixes
     * @return array of array[0..n] of array[type, array attributes]
     * @see IMDB page / (specifications)
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
     * @return array of array[0..n] of array[type, array attributes]
     * @see IMDB page / (specifications)
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
     * @return array of array[0..n] of array[aspectRatio, array attributes]
     * @see IMDB page / (specifications)
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
     * @return array of array[0..n] of array[cameras, array attributes]
     * @see IMDB page / (specifications)
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
     * @return array[] of array(authorNickName| string, authorRating| int, summaryText| string, reviewText| string, submissionDate| iso date string)
     * @see IMDB page / (TitlePage)
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
            if (!empty($data->title->featuredReviews->edges)) {
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
     * @return boolean
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
            $this->isAdult = $data->title->isAdult;
        }
        return $this->isAdult;
    }

    #----------------------------------------------------------[ Watch Option ]---
    /**
     * watch options by category for this title
     * @Note: (DEC 2024) Only Amazon providers are returned, no others!
     * @return categorized array()
     *  [rent/buy] => Array
     *      [0] => Array
     *          [providerId] =>     (string) amzn1.imdb.w2w.provider.prime_video
     *          [providerName] =>   (string) Prime Video
     *          [logoUrl] =>        (string) (PNG!) https://m.media-amazon.com/images/M/4c6e._V1_QL100_UX250_.png
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
            foreach ($data->title->watchOptionsByCategory->categorizedWatchOptionsList as $item) {
                $watchOptions = array();
                $categoryName = strtolower(str_replace('/', '-', $item->categoryName->value));
                if (!empty($item->watchOptions)) {
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
        return $this->watchOption;
    }

    #----------------------------------------------------------[ Production Status ]---
    /**
     * Get current production status of a title e.g. Released, In Development, Pre-Production, Complete, Production etc
     * @return string status
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
            $this->status = isset($data->title->productionStatus->currentProductionStage->text) ? 
                                  $data->title->productionStatus->currentProductionStage->text : null;
        }
        return $this->status;
    }

    #----------------------------------------------------------[ News ]---
    /**
     * Get news items about this title, max 100 items!
     * @return array of array()
     *      [id] =>                 (string)
     *      [title] =>              (string) e.g. The best movies on Netflix right now
     *      [author] =>             (string) e.g. The A.V. Club
     *      [date] =>               (string) e.g. 2024-12-01T02:00:00Z
     *      [extUrl] =>             (string) e.g. https://www.avclub.com/1842540580
     *      [extHomepageUrl] =>     (string) e.g. http://www.avclub.com/content/home
     *      [extHomepageLabel] =>   (string) e.g. avclub.com
     *      [textHtml] =>           (string) (including html)
     *      [textText] =>           (string)
     *      [thumbnailUrl] =>       (string)
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
        return $this->news;
    }


    #========================================================[ Helper functions ]===
    #===============================================================================

    /**
     * Setup title and year properties
     */
    protected function titleYear()
    {
        $query = <<<EOF
query TitleYear(\$id: ID!) {
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
        $data = $this->graphql->query($query, "TitleYear", ["id" => "tt$this->imdbID"]);

        $this->mainTitle = isset($data->title->titleText->text) ?
                                 trim(str_replace('"', ':', trim($data->title->titleText->text, '"'))) : null;
        $this->mainOriginalTitle  = isset($data->title->originalTitleText->text) ?
                                          trim(str_replace('"', ':', trim($data->title->originalTitleText->text, '"'))) : null;
        $this->mainMovietype = isset($data->title->titleType->text) ?
                                     $data->title->titleType->text : null;
        $this->mainYear = isset($data->title->releaseYear->year) ?
                                $data->title->releaseYear->year : null;
        $this->mainEndYear = isset($data->title->releaseYear->endYear) ?
                                   $data->title->releaseYear->endYear : null;
    }

    #========================================================[ photo/poster ]===
    /**
     * Setup cover photo (thumbnail and big variant)
     * @see IMDB page / (TitlePage)
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

    #========================================================[ CompanyCredits ]===
    /**
     * Fetch all company credits
     * @param string $category e.g. distribution, production
     * @return array<array{name: string, id: string, country: string, attribute: string, year: string}>
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
        foreach ($data as $edge) {
            $companyAttribute = array();
            if (!empty($edge->node->attributes)) {
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
                                $edge->node->yearsInvolved->year : null,
            );
        }
        return $results;
    }

    #========================================================[ Crew Category ]===
    #---------------------------------------------------------------[ credit helper ]---
    /** helper for stunts, thanks, visualEffects, specialEffects, producer,
     *      writer, director, composer, cinematographer
     * @return array (array[0..n] of arrays[imdb, name, jobs array[], attributes array[],
     *      episode array(total, year, endYear)], titleFullImageUrl, titleThumbImageUrl)
     * @see IMDB page /fullcredits
     */
    private function creditHelper($crewCategory)
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
        foreach ($data as $edge) {
            $jobs = array();
            if (!empty($edge->node->jobs)) {
                foreach ($edge->node->jobs as $value) {
                    if (!empty($value->text)) {
                        $jobs[] = $value->text;
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
            if (!empty($edge->node->attributes)) {
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
        return $output;
    }

    #----------------------------------------------------------[ Episode build filter]---
    /**
     * Build filter constraint for episode()
     * @param $seasonYear node->text from season or year
     * @return string $filter
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

    #========================================================[ Season Year check ]===
    /** Check if TV Series season or year based
     * @return array $data based on years or seasons
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
            $data = null;
        }
        return $data;

    }

    #========================================================[ GraphQL Get All Episodes]===
    /**
     * Get all episodes of a title
     * @param $filter add filter options to query
     * @return \stdClass[]
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

        // Results are paginated, so loop until we've got all the data
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

    #========================================================[ Helper Technical specifications ]===
    /**
     * Get movie tech specs
     * @param $type input techspec type like soundMixes or aspectRatios
     * @param $valueType input type like text or soundMix
     * @param $arrayName output array name
     * @return array of array[0..n] of array[type, array attributes]
     * @see IMDB page / (specifications)
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
        if (!empty($data->title->technicalSpecifications->$type->items)) {
            foreach ($data->title->technicalSpecifications->$type->items as $item) {
                $attributes = array();
                if (!empty($item->attributes)) {
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

    #========================================================[ GraphQL Get All]===
    /**
     * Get all edges of a field in the title type
     * @param string $queryName The cached query name
     * @param string $fieldName The field on title you want to get
     * @param string $nodeQuery Graphql query that fits inside node { }
     * @param string $filter Add's extra Graphql query filters like categories
     * @return \stdClass[]
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

        // Results are paginated, so loop until we've got all the data
        $endCursor = null;
        $hasNextPage = true;
        $edges = array();
        while ($hasNextPage) {
            $data = $this->graphql->query($fullQuery, $queryName, ["id" => "tt$this->imdbID", "after" => $endCursor]);
            $edges = array_merge($edges, $data->title->{$fieldName}->edges);
            $hasNextPage = $data->title->{$fieldName}->pageInfo->hasNextPage;
            $endCursor = $data->title->{$fieldName}->pageInfo->endCursor;
        }
        return $edges;
    }

    #----------------------------------------------------------[ imdbID redirect ]---
    /**
     * Check if imdbid is redirected to another id or not.
     * It sometimes happens that imdb redirects an existing id to a new id.
     * If user uses search class this check isn't nessecary as the returned results already contain a possible new imdbid
     * @var $this->imdbID The imdbid used to call this class
     * @var $titleImdbId the returned imdbid from Graphql call (in some cases this can be different)
     * @return $titleImdbId (the new redirected imdbId) or false (no redirect)
     * @see IMDB page / (TitlePage)
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
        $titleImdbId = str_replace('tt', '', $data->title->meta->canonicalId);
        if ($titleImdbId  != $this->imdbID) {
            // todo write to log?
            return $titleImdbId;
        } else {
            return false;
        }
    }

    #----------------------------------------------------------[ Build date string for Episode() ]---
    /**
     * build date string for episode()
     * @param array $date input date array(['day'], ['month'], ['year'])
     * @return string $airDate e.g. '20 jan. 2008'
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

}
