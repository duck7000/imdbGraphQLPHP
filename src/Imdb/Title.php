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
    protected $mainPoster = "";
    protected $mainPosterThumb = "";
    protected $mainPlotoutline = "";
    protected $mainMovietype = "";
    protected $mainTitle = "";
    protected $mainOriginalTitle = "";
    protected $mainYear = -1;
    protected $mainEndYear = -1;
    protected $mainTop250 = 0;
    protected $mainRating = 0;
    protected $mainRatingVotes = 0;
    protected $mainMetacritics = 0;
    protected $mainRank = array();
    protected $mainPhoto = array();
    protected $trailers = array();
    protected $mainAwards = array();
    protected $awards = array();
    protected $genres = array();
    protected $quotes = array();
    protected $recommendations = array();
    protected $runtimes = array();
    protected $mpaas = array();
    protected $plot = array();
    protected $seasonEpisodes = array();
    protected $soundtracks = array();
    protected $taglines = array();
    protected $trivias = array();
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
        if ($this->mainTitle == "") {
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
        if ($this->mainOriginalTitle  == "") {
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
                $this->runtimes[] = array(
                    "time" => $edge->node->seconds / 60,
                    "annotations" => array_map(function ($attribute) {
                        return $attribute->text;
                    }, $edge->node->attributes),
                    "country" => isset($edge->node->country->text) ? $edge->node->country->text : null
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
                $this->mainRank['currentRank'] = $data->title->meterRanking->currentRank;

                $this->mainRank['changeDirection'] = isset($data->title->meterRanking->rankChange->changeDirection) ?
                                                            $data->title->meterRanking->rankChange->changeDirection : null;

                $this->mainRank['difference'] = isset($data->title->meterRanking->rankChange->difference) ?
                                                       $data->title->meterRanking->rankChange->difference : -1;
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
            if (!empty($data)) {
                foreach ($data as $edge) {
                    $this->faqs[] = array(
                        'question' => isset($edge->node->question->plainText) ? $edge->node->question->plainText : '',
                        'answer' => isset($edge->node->answer->plainText) ? $edge->node->answer->plainText : '',
                        'isSpoiler' => $edge->node->isSpoiler
                    );
                }
            } else {
                return $this->faqs;
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
                $thumb = '';
                if (!empty($edge->node->primaryImage->url)) {
                    $fullImageWidth = $edge->node->primaryImage->width;
                    $fullImageHeight = $edge->node->primaryImage->height;
                    $newImageWidth = 140;
                    $newImageHeight = 207;
                    $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                    $thumb = $img . $parameter;
                }
                $this->recommendations[] = array(
                    "title" => $edge->node->titleText->text,
                    "imdbid" => str_replace('tt', '', $edge->node->id),
                    "rating" => isset($edge->node->ratingsSummary->aggregateRating) ? $edge->node->ratingsSummary->aggregateRating : -1,
                    "img" => $thumb,
                    "year" => isset($edge->node->releaseYear->year) ? $edge->node->releaseYear->year : -1
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
                    $this->languages[] = $language->text;
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
                            $subGenres[] = ucwords($subGenre->keyword->text->text);
                        }
                    }
                    $this->genres[] = array(
                        'mainGenre' => $edge->genre->text,
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
        if ($this->mainPlotoutline == "") {
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
                    $this->countries[] = $country->text;
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
                    $country = isset($edge->node->country->text) ? $edge->node->country->text : '';
                    $attributes = array();
                    if (!empty($edge->node->attributes)) {
                        foreach ($edge->node->attributes as $attribute) {
                            $attributes[] = $attribute->text;
                        }
                    }
                    $this->releaseDates[] = array(
                        'country' => $country,
                        'day' => $edge->node->day,
                        'month' => $edge->node->month,
                        'year' => $edge->node->year,
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
                foreach ($edge->node->attributes as $attribute) {
                    if (!empty($attribute->text)) {
                        $comments[] = $attribute->text;
                    }
                }
                $this->akas[] = array(
                    'title' => ucwords($edge->node->text),
                    'country' => isset($edge->node->country->text) ? ucwords($edge->node->country->text) : 'Unknown',
                    'countryId' => isset($edge->node->country->id) ? $edge->node->country->id : null,
                    'language' => isset($edge->node->language->text) ? ucwords($edge->node->language->text) : null,
                    'languageId' => isset($edge->node->language->id) ? $edge->node->language->id : null,
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
                foreach ($edge->node->attributes as $key => $attribute) {
                    if (!empty($attribute->text)) {
                        $comments[] = $attribute->text;
                    }
                }
                $this->mpaas[] = array(
                    "country" => isset($edge->node->country->text) ? $edge->node->country->text : '',
                    "rating" => isset($edge->node->rating) ? $edge->node->rating : '',
                    "comment" => $comments
                );
            }
        }
        return $this->mpaas;
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
            if (isset($data->title->ratingsSummary->topRanking->rank) && $data->title->ratingsSummary->topRanking->rank <= 250) {
                $this->mainTop250 = $data->title->ratingsSummary->topRanking->rank;
            }
        }
        return $this->mainTop250;
    }

    #=====================================================[ /plotsummary page ]===
    /** Get movie plots without Spoilers
     * @return array array[0..n] string plot, string author]
     * @see IMDB page /plotsummary
     */
    public function plot()
    {
        if (empty($this->plot)) {
            $query = <<<EOF
query Plots(\$id: ID!) {
  title(id: \$id) {
    plots(first: 9999, filter: {spoilers: EXCLUDE_SPOILERS}) {
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
            foreach ($data->title->plots->edges as $key => $edge) {
                $this->plot[] = array(
                    'plot' => $edge->node->plotText->plainText,
                    'author' => isset($edge->node->author) ? $edge->node->author : ''
                );
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
                $this->taglines[] = $edge->node->text;
            }
        }
        return $this->taglines;
    }

    #=====================================================[ /fullcredits page ]===
    #----------------------------------------------------------------[ PrincipalCredits ]---
    /*
    * Get the PrincipalCredits for this title
    * @return array creditsPrincipal[category][Director, Writer, Creator, Stars] (array[0..n] of array[name,imdbid])
    * Not all categories are always available, TV series has Creator instead of writer
    */
    public function principalCredits()
    {
        if (empty($this->creditsPrincipal)) {
            $query = <<<EOF
query PrincipalCredits(\$id: ID!) {
  title(id: \$id) {
    principalCredits {
      credits {
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
                $cat = $value->credits[0]->category->text;
                if ($cat == "Actor" || $cat == "Actress") {
                    $category = "Star";
                } else {
                    $category = $cat;
                }
                $temp = array();
                foreach ($value->credits as $key => $credit) {
                    $temp[] = array(
                        'name' => isset($credit->name->nameText->text) ? $credit->name->nameText->text : '',
                        'imdbid' => isset($credit->name->id) ? str_replace('nm', '', $credit->name->id) : ''
                    );
                    if ($key == 2) {
                        break;
                    }
                }
                $this->creditsPrincipal[$category] = $temp;
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
        if (!empty($this->creditsCast)) {
            return $this->creditsCast;
        }
        $filter = ', filter: { categories: ["cast"] }';
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
            $name = isset($edge->node->name->nameText->text) ? $edge->node->name->nameText->text : '';
            $imdb = isset($edge->node->name->id) ? str_replace('nm', '', $edge->node->name->id) : '';
            
            // character
            $castCharacters = array();
            if (!empty($edge->node->characters)) {
                foreach ($edge->node->characters as $keyCharacters => $character) {
                    $castCharacters[] = $character->name;
                }
            }
            
            // comment, name_alias and credited
            $comments = array();
            $name_alias = null;
            $credited = true;
            if (!empty($edge->node->attributes)) {
                foreach ($edge->node->attributes as $keyAttributes => $attribute) {
                    if (strpos($attribute->text, "as ") !== false) {
                        $name_alias = trim(ltrim($attribute->text, "as"));
                    } elseif (stripos($attribute->text, "uncredited") !== false) {
                        $credited = false;
                    } else {
                        $comments[] = $attribute->text;
                    }
                }
            }
            
            // image url
            $imgUrl = '';
            if (!empty($edge->node->name->primaryImage->url)) {
                $fullImageWidth = $edge->node->name->primaryImage->width;
                $fullImageHeight = $edge->node->name->primaryImage->height;
                $newImageWidth = 32;
                $newImageHeight = 44;

                $img = str_replace('.jpg', '', $edge->node->name->primaryImage->url);

                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                $imgUrl = $img . $parameter;
            }
            
            $this->creditsCast[] = array(
                'imdb' => $imdb,
                'name' => $name,
                'name_alias' => $name_alias,
                'credited' => $credited,
                'character' => $castCharacters,
                'comment' => $comments,
                'thumb' => $imgUrl
            );
        }
        return $this->creditsCast;
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
        $directorData = $this->creditHelper($this->creditsQuery("director"));
        
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
        $cinematographerData = $this->creditHelper($this->creditsQuery("cinematographer"));
        
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
        $data = $this->creditHelper($this->creditsQuery("writer"));
        return $this->creditsWriter = $data;
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
        $data = $this->creditHelper($this->creditsQuery("producer"));
        return $this->creditsProducer = $data;
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
        $composerData = $this->creditHelper($this->creditsQuery("composer"));
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
        $data = $this->creditHelper($this->creditsQuery("stunts"));
        return $this->creditsStunts = $data;
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
        $data = $this->creditHelper($this->creditsQuery("thanks"));
        return $this->creditsThanks = $data;
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
        $data = $this->creditHelper($this->creditsQuery("visual_effects"));
        return $this->creditsVisualEffects = $data;
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
        $data = $this->creditHelper($this->creditsQuery("special_effects"));
        return $this->creditsSpecialEffects = $data;
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
displayableArticle {
  body {
    plainText
  }
}
EOF;
            $data = $this->graphQlGetAll("CrazyCredits", "crazyCredits", $query);
            foreach ($data as $edge) {
                if (!empty($edge->node->displayableArticle->body->plainText)) {
                    $this->crazyCredits[] = preg_replace('/\s\s+/', ' ', $edge->node->displayableArticle->body->plainText);
                }
            }
        }
        return $this->crazyCredits;
    }

    #========================================================[ /episodes page ]===
    #--------------------------------------------------------[ Episodes Array ]---
    /**
     * Get the series episode(s)
     * @return array episodes (array[0..n] of array[0..m] of array[imdbid,title,airdate,airdateParts array(day,month,year),plot,season,episode,imgUrl])
     * array(1) {
     *   [1]=>
     *   array(13) {
     *       [1]=> //can be seasonnumber, year or -1 (Unknown)
     *       array(6) {
     *       ["imdbid"]=>
     *       string(7) "1495166"
     *       ["title"]=>
     *       string(5) "Pilot"
     *       ["airdate"]=>
     *       string(11) "7 jun. 2010"
     *       [airdateParts] => Array
     *                   (
     *                       [day] => 7
     *                       [month] => 6
     *                       [year] => 2010
     *                   )
     *       ["plot"]=>
     *       string(648) "Admirably unselfish fireman Joe Tucker takes charge when he and six others..
     *       ["episode"]=>
     *       string(1) "1" //can be seasonnumber or -1 (Unknown)
     *       ["imgUrl"]=>
     *       string(108) "https://m.media-amazon.com/images/M/MV5BMjM3NjI2MDA2OF5BMl5BanBnXkFtZTgwODgwNjEyMjE@._V1_UY126_UX224_AL_.jpg"
     *       }
     *   }
     * @see IMDB page /episodes
     * @param $thumb boolean true: thumbnail (cropped from center 224x126), false: large (max 1000 pixels)
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
                    if (strlen((string)$edge->node->text) === 4) {
                        // year based Tv Series
                        $seasonYear = $edge->node->text;
                        $filter = 'filter: { releasedOnOrAfter: { day: 1, month: 1, year: ' . $seasonYear . '}, releasedOnOrBefore: { day: 31, month: 12, year: ' . $seasonYear . '}}';
                    } else {
                        $seasonYear = $edge->node->text;
                        // To fetch data from unknown seasons/years
                        if ($edge->node->text == "Unknown") { //this is intended capitol
                            $SeasonUnknown = "unknown"; //this is intended not capitol
                            $seasonFilter = "";
                            $seasonYear = -1;
                        } else {
                            $seasonFilter = $edge->node->text;
                            $SeasonUnknown = "";
                        }
                        $filter = 'filter: { includeSeasons: ["' . $seasonFilter . '", "' . $SeasonUnknown . '"] }';
                    }
                    
                    // Get all episodes
                    $episodesData = $this->graphQlGetAllEpisodes($filter);
                    
                    $episodes = array();
                    foreach ($episodesData as $keyEp => $edge) {
                        // vars
                        $airDate = '';
                        $epNumber = '';
                        $imgUrl = '';
                        // Episode ImdbId
                        $imdbId = isset($edge->node->id) ? str_replace('tt', '', $edge->node->id) : '';
                        // Episode Title
                        $title = isset($edge->node->titleText->text) ? $edge->node->titleText->text : '';
                        // Episode Airdate
                        $day = isset($edge->node->releaseDate->day) ? $edge->node->releaseDate->day : null;
                        $month = isset($edge->node->releaseDate->month) ? $edge->node->releaseDate->month : null;
                        $year = isset($edge->node->releaseDate->year) ? $edge->node->releaseDate->year : null;
                        $dateParts = array(
                            'day' => $day,
                            'month' => $month,
                            'year' => $year
                        );
                        // return airdate as string.
                        if (!empty($day)) {
                            $airDate .= $day;
                            if (!empty($month)) {
                                $airDate .= ' ';
                            }
                        }
                        if (!empty($month)) {
                            $airDate .= date('M', mktime(0, 0, 0, $month, 10)) . '.';
                            if (!empty($year)) {
                                $airDate .= ' ';
                            }
                        }
                        if (!empty($year)) {
                            $airDate .= $year;
                        }
                        // Episode Plot
                        $plot = isset($edge->node->plot->plotText->plainText) ? $edge->node->plot->plotText->plainText : '';
                        // Episode Number
                        if (isset($edge->node->series->displayableEpisodeNumber->episodeNumber->episodeNumber)) {
                            $epNumber = $edge->node->series->displayableEpisodeNumber->episodeNumber->episodeNumber;
                            // Unknown episodes get a number to keep them seperate.
                            if ($epNumber == "unknown") {
                                $epNumber = -1;
                            }
                        }
                        // Episode Image
                        if (!empty($edge->node->primaryImage->url)) {
                            if ($thumb == true) {
                                $epImageUrl = $edge->node->primaryImage->url;
                                $fullImageWidth = $edge->node->primaryImage->width;
                                $fullImageHeight = $edge->node->primaryImage->height;
                                $newImageWidth = 224;
                                $newImageHeight = 126;

                                $img = str_replace('.jpg', '', $epImageUrl);

                                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                                $imgUrl = $img . $parameter;
                            } else {
                                $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                                $imgUrl = $img . 'QL100_SY1000_.jpg';
                            }
                        }
                        $episode = array(
                                'imdbid' => $imdbId,
                                'title' => $title,
                                'airdate' => $airDate,
                                'airdateParts' => $dateParts,
                                'plot' => $plot,
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
                                       $edge->node->displayableArticle->body->plainText : '',
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
                            'name' => $name->nameText->text,
                            'id' => str_replace('nm', '', $name->id)
                        );
                    }
                }
                $this->trivias[$categoryIds[$edge->node->category->id]][] = array(
                    'content' => isset($edge->node->displayableArticle->body->plainText) ?
                                       preg_replace('/\s\s+/', ' ', $edge->node->displayableArticle->body->plainText) : '',
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
     * [creditSplit]: credits split by type, name and nameId
     * [comments]: if not a credited person, it is considered comment as plain text
     * Array(
     *  [0] => Array
     *   (
     *       [soundtrack] => We've Only Just Begun
     *       [credits] => Array
     *           (
     *               [0] => Written by Roger Nichols (as Roger S. Nichols) and Paul Williams (as Paul H. Williams)
     *               [1] => Performed by The Carpenters
     *               [2] => Courtesy of A&M Records
     *               [3] => Under license from Universal Music Enterprises
     *           )
     *       [creditSplit] => Array
     *           (
     *               [0] => Array
     *                   (
     *                       [creditType] => Writer
     *                       [name] => Roger Nichols
     *                       [nameId] => 0629720
     *                   )
     *               [1] => Array
     *                   (
     *                       [creditType] => Writer
     *                       [name] => Paul Williams
     *                       [nameId] => 0931437
     *                   )
     *               [2] => Array
     *                   (
     *                       [creditType] => Performer
     *                       [name] => The Carpenters
     *                       [nameId] => 1135559
     *                   )
     *
     *           )
     *       [comment] => Array
     *           (
     *               [0] => Courtesy of A&M Records
     *               [1] => Under license from Universal Music Enterprises
     *           )
     *   )
     * )
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
                if (!empty($edge->node->text)) {
                    $title = trim($edge->node->text);
                } else {
                    $title = 'Unknown';
                }
                $credits = array();
                $creditComments = array();
                $crediters = array();
                if (!empty($edge->node->comments)) {
                    foreach ($edge->node->comments as $key => $comment) {
                        if (stripos($comment->plaidHtml, "arrangement with") === FALSE) {
                            
                            // check and replace :?
                            $pos2 = strpos($comment->plaidHtml, ":");
                            if ($pos2 !== false) {
                                $comment->plaidHtml = substr_replace($comment->plaidHtml, " by", $pos2, strlen(":"));
                            }
                            
                            if (($pos = stripos($comment->plaidHtml, "by")) !== FALSE) {
                                
                                // split at "by"
                                $creditRaw = substr($comment->plaidHtml, $pos + 2);
                                $creditType = trim(substr($comment->plaidHtml, 0, $pos + 2), "[] ");

                                // replace some elements (& and), explode it in array
                                $patterns = array('/^.*?\K&amp;(?![^>]*\/\s*a\s*>)/',
                                                  '/^.*?\Kand(?![^>]*\/\s*a\s*>)/');
                                $creditRaw = preg_replace($patterns, ',', $creditRaw);
                                $creditRawParts = explode(",", $creditRaw);
                                $creditRawParts = array_values(array_filter($creditRawParts));
                                
                                // loop $creditRawParts array 
                                foreach ($creditRawParts as $value) {
                                    // get anchor links
                                    libxml_use_internal_errors(true);
                                    $doc = new \DOMDocument();
                                    $doc->loadHTML('<?xml encoding="UTF-8">' . $value);
                                    $anchors = $doc->getElementsByTagName('a');
                                    
                                    // check what $anchors contains
                                    if ($anchors != null && $anchors->length > 0) {
                                        $href = $anchors->item(0)->attributes->getNamedItem('href')->nodeValue;
                                        $id = preg_replace('/[^0-9]+/', '', $href);
                                        $crediters[] = array(
                                            'creditType' => $creditType,
                                            'name' => trim($anchors->item(0)->nodeValue),
                                            'nameId' => $id
                                        );
                                    } else {
                                        // no anchor, text only, check if id is present in text form
                                        if (preg_match('/(nm?\d+)/', $value, $match)) {
                                            $nameId = preg_replace('/[^0-9]+/', '', $match[0]);
                                            $name = '';
                                        } else {
                                            $nameId = '';
                                            $name = trim($value, "[] ");
                                        }
                                        $crediters[] = array(
                                            'creditType' => $creditType,
                                            'name' => $name,
                                            'nameId' => $nameId
                                        );
                                    }
                                }
                            } else {
                                // no by, treat as comment in plain text
                                if (!empty(trim(strip_tags($comment->plaidHtml)))) {
                                    $creditComments[] = trim(strip_tags($comment->plaidHtml));
                                }
                            }
                        } else {
                            // no arrangement with, treat as comment in plain text
                            if (!empty(trim(strip_tags($comment->plaidHtml)))) {
                                $creditComments[] = trim(strip_tags($comment->plaidHtml));
                            }
                        }
                        // add data to $credits as plain text
                        if (!empty(trim(strip_tags($comment->plaidHtml)))) {
                            $credits[] = trim(strip_tags($comment->plaidHtml));
                        }
                    }
                }
                $this->soundtracks[] = array(
                    'soundtrack' => $title,
                    'credits' => $credits,
                    'creditSplit' => $crediters,
                    'comment' => $creditComments
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
                $real = isset($edge->node->displayableProperty->value->plainText) ? $edge->node->displayableProperty->value->plainText : '';
                $movie = array();
                if (!empty($edge->node->displayableProperty->qualifiersInMarkdownList)) {
                    foreach ($edge->node->displayableProperty->qualifiersInMarkdownList as $attribute) {
                        $movie[] = $attribute->plainText;
                    }
                }
                $this->locations[] = array(
                    'real' => $real,
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
        if (empty($this->ccompCreditsOther)) {
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
                    'titleId' => str_replace('tt', '', $edge->node->associatedTitle->id),
                    'titleName' => $edge->node->associatedTitle->titleText->text,
                    'titleType' => isset($edge->node->associatedTitle->titleType->text) ?
                                         $edge->node->associatedTitle->titleType->text : '',
                    'year' => isset($edge->node->associatedTitle->releaseYear->year) ?
                                    $edge->node->associatedTitle->releaseYear->year : -1,
                    'endYear' => isset($edge->node->associatedTitle->releaseYear->endYear) ?
                                       $edge->node->associatedTitle->releaseYear->endYear : -1,
                    'seriesName' => isset($edge->node->associatedTitle->series->series->titleText->text) ?
                                          $edge->node->associatedTitle->series->series->titleText->text : '',
                    'description' => isset($edge->node->description->plainText) ?
                                           $edge->node->description->plainText : ''
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
        $categoryIds = array(
            'official' => 'official',
            'video' => 'video',
            'photo' => 'photo',
            'sound' => 'sound',
            'misc' => 'misc'
        );

        if (empty($this->externalSites)) {
            foreach ($categoryIds as $categoryId) {
                $this->externalSites[$categoryId] = array();
            }
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
                $label = null;
                $url = null;
                $language = array();
                if (!empty($edge->node->url)) {
                    $url = $edge->node->url;
                    $label = $edge->node->label;
                }
                if (!empty($edge->node->externalLinkLanguages)) {
                    foreach ($edge->node->externalLinkLanguages as $lang) {
                        $language[] = isset($lang->text) ? $lang->text : null;
                    }
                }
                $this->externalSites[$categoryIds[$edge->node->externalLinkCategory->id]][] = array(
                    'label' => $label,
                    'url' => $url,
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
            if (!empty($data->title->productionBudget) && !empty($data->title->productionBudget->budget->amount)) {
                $this->productionBudget["amount"] = $data->title->productionBudget->budget->amount;
                $this->productionBudget["currency"] = $data->title->productionBudget->budget->currency;
            } else {
                return $this->productionBudget;
            }
        }
        return $this->productionBudget;
    }

    #-------------------------------------------------------[ rankedLifetimeGrosses ]---
    /** Info about Grosses, ranked by amount
     * @return array[] array[areatype, amount, currency]>
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
                    $areatype = $edge->node->boxOfficeAreaType->text;
                    $amount = isset($edge->node->total->amount) ? $edge->node->total->amount : '';
                    $currency = isset($edge->node->total->currency) ? $edge->node->total->currency : '';
                } else {
                    continue;
                }
                $this->grosses[] = array(
                    "areatype" => $areatype,
                    "amount" => $amount,
                    "currency" => $currency
                );
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
            if (!empty($data)) {
                foreach ($data as $edge) {
                    if (!empty($edge->node->keyword->text->text)) {
                        $this->keywords[] = $edge->node->keyword->text->text;
                    }
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
                $this->alternateversions[] = $edge->node->text->plainText;
            }
        }
        return $this->alternateversions;
    }

    #-------------------------------------------------[ Main images ]---
    /**
     * Get image URLs for (default 6) pictures from photo page
     * @param $amount, int for how many images, max = 9999
     * @param $thumb boolean, true: thumbnail cropped from center 100x100 pixels false: untouched max 1000 pixels
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
                        // calculate crop value
                        $cropParameter = $this->imageFunctions->thumbUrlCropParameter($fullImageWidth, $fullImageHeight, 100, 100);


                        // original source aspect ratio
                        $ratio_orig = $fullImageWidth / $fullImageHeight;
                        // new aspect ratio
                        $ratio_new = 100 / 100;

                        if ($ratio_new < $ratio_orig) {
                            // Landscape (Y)
                            $orientation = 'Y';
                        } else {
                            // portrait (X)
                            $orientation = 'X';
                        }
                        $this->mainPhoto[] = $imgUrl . 'QL75_S' . $orientation . '100_CR' . $cropParameter . ',0,100,100_AL_.jpg';
                    } else {
                        $this->mainPhoto[] = $imgUrl . 'QL100_SY1000_.jpg';
                    }
                }
            }
        }
        return $this->mainPhoto;
    }
    
    #-------------------------------------------------[ Trailer ]---
    /**
     * Get video URL's and images from videogallery page (Trailers only)
     * @param $amount determine how many trailers are returned, default: 1
     * @return array trailers (array[string videoUrl,string videoImageUrl])
     * videoUrl is a embeded url that is tested to work in iframe (won't work in html5 <video>)
     */
    public function trailer($amount = 1)
    {
        if (empty($this->trailers)) {
            $query = <<<EOF
query Video(\$id: ID!) {
  title(id: \$id) {
    primaryVideos(first: 9999) {
      edges {
        node {
          playbackURLs {
            url
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
          primaryTitle {
            titleText {
              text
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
                // check if url and contentType is set and contentType = Trailer
                if (!isset($edge->node->playbackURLs[0]->url) ||
                    !isset($edge->node->contentType->displayName->value) ||
                    $edge->node->contentType->displayName->value !== "Trailer") {
                    continue;
                }

                // Video ID
                $videoId = explode("/", parse_url($edge->node->playbackURLs[0]->url, PHP_URL_PATH));

                // Embed URL
                $embedUrl = "https://www.imdb.com/video/imdb/" . $videoId[1] . "/imdb/embed";

                // Check if embed URL not == 404 or 401
                $headers = get_headers($embedUrl);
                if (substr($headers[0], 9, 3) == "404" || substr($headers[0], 9, 3) == "401") {
                    continue;
                }
                $thumbUrl = '';
                if (!empty($edge->node->thumbnail->url)) {
                    // Runtime
                    $runtime = $edge->node->runtime->value;
                    $minutes = sprintf("%02d", ($runtime / 60));
                    $seconds = sprintf("%02d", $runtime % 60);

                    // Title
                    $title = rawurlencode(rawurlencode($edge->node->primaryTitle->titleText->text));

                    // calculate if the source image is HD aspect ratio or not
                    $fullImageWidth = $edge->node->thumbnail->width;
                    $fullImageHeight = $edge->node->thumbnail->height;
                    $HDicon = 'PIimdb-HDIconMiniWhite,BottomLeft,4,-2_';
                    $margin = '24';
                    $aspectRatio = $fullImageWidth / $fullImageHeight;
                    if ($aspectRatio < 1.77) {
                        $HDicon = '';
                        $margin = '4';
                    } elseif ($fullImageWidth < 1280) {
                        $HDicon = '';
                        $margin = '4';
                    } elseif ($fullImageHeight < 720) {
                        $HDicon = '';
                        $margin = '4';
                    }

                    // Thumbnail URL
                    $thumbUrl = str_replace('.jpg', '', $edge->node->thumbnail->url);
                    $thumbUrl .= '1_SP330,330,0,C,0,0,0_CR65,90,200,150_'
                                 . 'PIimdb-blackband-204-14,TopLeft,0,0_'
                                 . 'PIimdb-blackband-204-28,BottomLeft,0,1_CR0,0,200,150_'
                                 . 'PIimdb-bluebutton-big,BottomRight,-1,-1_'
                                 . 'ZATrailer,4,123,16,196,verdenab,8,255,255,255,1_'
                                 . 'ZAon%2520IMDb,4,1,14,196,verdenab,7,255,255,255,1_'
                                 . 'ZA' . $minutes . '%253A' . $seconds .',164,1,14,36,verdenab,7,255,255,255,1_'
                                 . $HDicon
                                 . 'ZA' . $title . ',' . $margin . ',138,14,176,arialbd,7,255,255,255,1_.jpg';

                }
                if (count($this->trailers) < $amount) {
                    $this->trailers[] = array(
                        'videoUrl' => $embedUrl,
                        'videoImageUrl' => $thumbUrl
                    );
                }
            }
        }
        return $this->trailers;
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
            $this->mainAwards['award'] = '';
            $this->mainAwards['nominations'] = '';
            $this->mainAwards['wins'] = '';
            if (!empty($data->title->prestigiousAwardSummary)) {
                $this->mainAwards['award'] = $data->title->prestigiousAwardSummary->award->text;
                $this->mainAwards['nominations'] = $data->title->prestigiousAwardSummary->nominations;
                $this->mainAwards['wins'] = $data->title->prestigiousAwardSummary->wins;
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
        $winsOnly = $winsOnly === true ? "WINS_ONLY" : "null";
        $event = !empty($event) ? "events: " . '"' . trim($event) . '"' : "";

        $filter = ', sort: {by: PRESTIGIOUS, order: DESC}, filter: {wins: ' . $winsOnly . ' ' . $event . '}';

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
                $eventName = isset($edge->node->award->event->text) ? $edge->node->award->event->text : '';
                $eventEditionYear = isset($edge->node->award->eventEdition->year) ? $edge->node->award->eventEdition->year : '';
                $awardName = isset($edge->node->award->text) ? $edge->node->award->text : '';
                $awardCategory = isset($edge->node->award->category->text) ? $edge->node->award->category->text : '';
                $awardNotes = isset($edge->node->award->notes->plainText) ? $edge->node->award->notes->plainText : '';
                $awardIsWinner = $edge->node->isWinner;
                $conclusion = $awardIsWinner === true ? "Winner" : "Nominee";
                $awardIsWinner === true ? $winnerCount++ : $nomineeCount++;
                
                //credited persons
                $persons = array();
                if (!empty($edge->node->awardedEntities->secondaryAwardNames)) {
                    foreach ($edge->node->awardedEntities->secondaryAwardNames as $creditor) {
                        $creditName = isset($creditor->name->nameText->text) ? $creditor->name->nameText->text : '';
                        $creditId = isset($creditor->name->id) ? $creditor->name->id : '';
                        $creditNote = isset($creditor->note->plainText) ? $creditor->note->plainText : '';
                        $persons[] = array(
                            'creditId' => str_replace('nm', '', $creditId),
                            'creditName' => $creditName,
                            'creditNote' => trim($creditNote, " ()")
                        );
                    }
                }
                
                $this->awards[$eventName][] = array(
                    'awardYear' => $eventEditionYear,
                    'awardWinner' => $awardIsWinner,
                    'awardCategory' => $awardCategory,
                    'awardName' => $awardName,
                    'awardNotes' => $awardNotes,
                    'awardPersons' => $persons,
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
     * @return soundMix of array[0..n] of array[type, array attributes]
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
     * @return colors of array[0..n] of array[type, array attributes]
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
     * @return aspectRatio of array[0..n] of array[aspectRatio, array attributes]
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
     * @return camerars of array[0..n] of array[cameras, array attributes]
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
     * @return array[] of array(authorNickName| string, authorRating| int or null, summaryText| string, reviewText| string, submissionDate| iso date string)
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
                    'authorNickName' => isset($edge->node->author->nickName) ? $edge->node->author->nickName : null,
                    'authorRating' => isset($edge->node->authorRating) ? $edge->node->authorRating : null,
                    'summaryText' => isset($edge->node->summary->originalText) ? $edge->node->summary->originalText : null,
                    'reviewText' => isset($edge->node->text->originalText->plainText) ? $edge->node->text->originalText->plainText : null,
                    'submissionDate' => isset($edge->node->submissionDate) ? $edge->node->submissionDate : null
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
        $query = <<<EOF
query Adult(\$id: ID!) {
  title(id: \$id) {
    isAdult
  }
}
EOF;
        $data = $this->graphql->query($query, "Adult", ["id" => "tt$this->imdbID"]);
        return $data->title->isAdult;
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

        $this->mainTitle = trim(str_replace('"', ':', trim($data->title->titleText->text, '"')));
        $this->mainOriginalTitle  = trim(str_replace('"', ':', trim($data->title->originalTitleText->text, '"')));
        $this->mainMovietype = isset($data->title->titleType->text) ? $data->title->titleType->text : '';
        $this->mainYear = isset($data->title->releaseYear->year) ? $data->title->releaseYear->year : '';
        $this->mainEndYear = isset($data->title->releaseYear->endYear) ? $data->title->releaseYear->endYear : null;
        if ($this->mainYear == "????") {
            $this->mainYear = "";
        }
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
            $fullImageWidth = $data->title->primaryImage->width;
            $fullImageHeight = $data->title->primaryImage->height;
            $newImageWidth = 190;
            $newImageHeight = 281;
            $img = str_replace('.jpg', '', $data->title->primaryImage->url);
            $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            
            // thumb image
            $this->mainPosterThumb = $img . $parameter;
            
            // full image
            $this->mainPoster = $img . 'QL100_SX1000_.jpg';
        }
    }

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
countries {
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
            $companyId = isset($edge->node->company->id) ? str_replace('co', '', $edge->node->company->id ) : '';
            $companyName = isset($edge->node->displayableProperty->value->plainText) ? $edge->node->displayableProperty->value->plainText : '';
            $companyCountry = '';
            if (!empty($edge->node->countries) && !empty($edge->node->countries[0]->text)) {
                $companyCountry = $edge->node->countries[0]->text;
            }
            $companyAttribute = array();
            if (!empty($edge->node->attributes)) {
                foreach ($edge->node->attributes as $key => $attribute) {
                    $companyAttribute[] = $attribute->text;
                }
            }
            $companyYear = '';
            if (!empty($edge->node->yearsInvolved) && !empty($edge->node->yearsInvolved->year)) {
                $companyYear = $edge->node->yearsInvolved->year;
            }
            $results[] = array(
                "name" => $companyName,
                "id" => $companyId,
                "country" => $companyCountry,
                "attribute" => $companyAttribute,
                "year" => $companyYear,
            );
        }
        return $results;
    }

    #========================================================[ Crew Category ]===
    /** create query and fetch data from category
     * @param string $crewCategory (producer, writer, composer or director)
     * @return array data (array[0..n] of objects)
     * @see used by the methods director, writer, producer, composer
     */
    private function creditsQuery($crewCategory)
    {
        $filter = ', filter: { categories: ["' .$crewCategory . '"] }';
        $query = <<<EOF
name {
  nameText {
    text
  }
  id
  primaryImage {
    url
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
    edges {
      node {
        title {
          series {
            displayableEpisodeNumber {
              episodeNumber {
                text
              }
            }
          }
        }
      }
    }
    yearRange {
      year
      endYear
    }
  }
}
EOF;
        $data = $this->graphQlGetAll("CreditCrew", "credits", $query, $filter);
        return $data;
    }

    #---------------------------------------------------------------[ credit helper ]---
    /** helper for stunts, thanks, visualEffects, specialEffects and producer
     * @return array (array[0..n] of arrays[imdb, name, jobs array[], attributes array[], episode array(total, year, endYear)], titleFullImageUrl, titleThumbImageUrl)
     * @see IMDB page /fullcredits
     */
    private function creditHelper($data)
    {
        $output = array();
        foreach ($data as $edge) {
            $name = isset($edge->node->name->nameText->text) ? $edge->node->name->nameText->text : '';
            $imdb = isset($edge->node->name->id) ? str_replace('nm', '', $edge->node->name->id) : '';
            $jobs = array();
            if (!empty($edge->node->jobs)) {
                foreach ($edge->node->jobs as $value) {
                    $jobs[] = $value->text;
                }
            }
            $totalEpisodes = 0;
            $year = null;
            $endYear = null;
            if (!empty($edge->node->episodeCredits)) {
                $totalEpisodes = count($edge->node->episodeCredits->edges);
                if (!empty($edge->node->episodeCredits->yearRange->year)) {
                    $year = $edge->node->episodeCredits->yearRange->year;
                    if (!empty($edge->node->episodeCredits->yearRange->endYear)) {
                        $endYear = $edge->node->episodeCredits->yearRange->endYear;
                    }
                }
            }
            $attributes = array();
            if (!empty($edge->node->attributes)) {
                foreach ($edge->node->attributes as $keyAttributes => $attribute) {
                    $attributes[] = isset($attribute->text) ? $attribute->text : null;
                }
            }
            $titleFullImageUrl = isset($edge->node->name->primaryImage->url) ?
                                    str_replace('.jpg', '', $edge->node->name->primaryImage->url) . 'QL100_SY1000_.jpg' : '';
            $titleThumbImageUrl = !empty($titleFullImageUrl) ?
                                    str_replace('QL100_SY1000_.jpg', '', $titleFullImageUrl) . 'QL75_SY98_.jpg' : '';
            $output[] = array(
                'imdb' => $imdb,
                'name' => $name,
                'jobs' => $jobs,
                'attributes' => $attributes,
                'episode' => array(
                    'total' => $totalEpisodes,
                    'year' => $year,
                    'endYear' => $endYear
                    ),
                'titleFullImageUrl' => $titleFullImageUrl,
                'titleThumbImageUrl' => $titleThumbImageUrl
            );
        }
        return $output;
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
                $type = isset($item->$valueType) ? $item->$valueType : '';
                $attributes = array();
                if (!empty($item->attributes)) {
                    foreach ($item->attributes as $attribute) {
                        $attributes[] = $attribute->text;
                    }
                }
                $arrayName[] = array(
                    'type' => $type,
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

}
