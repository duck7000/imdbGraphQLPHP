<?php
#############################################################################
# IMDBPHP6                             (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# written extended & maintained by Ed                                       #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;

/**
 * A title on IMDb
 * @author Georgos Giagas
 * @author Izzy (izzysoft AT qumran DOT org)
 * @author Ed
 * @copyright (c) 2002-2004 by Giorgos Giagas and (c) 2004-2009 by Itzchak Rehberg and IzzySoft
 */
class Title extends MdbBase
{

    protected $akas = array();
    protected $countries = array();
    protected $credits_cast = array();
    protected $principal_credits = array();
    protected $credits_composer = array();
    protected $credits_director = array();
    protected $credits_producer = array();
    protected $credits_writer = array();
    protected $langs = array();
    protected $all_keywords = array();
    protected $main_poster = "";
    protected $main_poster_thumb = "";
    protected $main_plotoutline = "";
    protected $main_movietype = "";
    protected $main_title = "";
    protected $main_original_title = "";
    protected $main_year = -1;
    protected $main_endyear = -1;
    protected $main_top250 = -1;
    protected $main_rating = -1;
    protected $main_photo = array();
    protected $trailers = array();
    protected $main_awards = array();
    protected $moviegenres = array();
    protected $moviequotes = array();
    protected $movierecommendations = array();
    protected $movieruntimes = array();
    protected $mpaas = array();
    protected $plot = array();
    protected $season_episodes = array();
    protected $isOngoing = null;
    protected $soundtracks = array();
    protected $taglines = array();
    protected $trivia = array();
    protected $goofs = array();
    protected $crazy_credits = array();
    protected $locations = array();
    protected $compcred_prod = array();
    protected $compcred_dist = array();
    protected $compcred_special = array();
    protected $compcred_other = array();
    protected $production_budget = array();
    protected $grosses = array();
    protected $moviealternateversions = array();

    /**
     * @param string $id IMDb ID. e.g. 285331 for https://www.imdb.com/title/tt0285331/
     * @param Config $config OPTIONAL override default config
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct($id, Config $config = null, CacheInterface $cache = null)
    {
        parent::__construct($config, $cache);
        $this->setid($id);
    }

    #-------------------------------------------------------------[ Title ]---
    /**
     * Setup title and year properties
     */
    protected function title_year()
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

        $this->main_title = ucwords(trim(str_replace('"', ':', trim($data->title->titleText->text, '"'))));
        $this->main_original_title = ucwords(trim(str_replace('"', ':', trim($data->title->originalTitleText->text, '"'))));
        $this->main_movietype = isset($data->title->titleType->text) ? $data->title->titleType->text : '';
        $this->main_year = isset($data->title->releaseYear->year) ? $data->title->releaseYear->year : '';
        $this->main_endyear = isset($data->title->releaseYear->endYear) ? $data->title->releaseYear->endYear : null;
        if ($this->main_year == "????") {
            $this->main_year = "";
        }
    }

    /** Get movie type
     * @return string movietype (TV Series, Movie, TV Episode, TV Special, TV Movie, TV Mini Series, Video Game, TV Short, Video)
     * @see IMDB page / (TitlePage)
     * If no movietype has been defined explicitly, it returns 'Movie' -- so this is always set.
     */
    public function movietype()
    {
        if (empty($this->main_movietype)) {
            $this->title_year();
            if (empty($this->main_movietype)) {
                $this->main_movietype = 'Movie';
            }
        }
        return $this->main_movietype;
    }

    /** Get movie title
     * @return string title movie title (name)
     * @see IMDB page / (TitlePage)
     */
    public function title()
    {
        if ($this->main_title == "") {
            $this->title_year();
        }
        return $this->main_title;
    }

    /** Get movie original title
     * @return string main_original_title movie original title
     * @see IMDB page / (TitlePage)
     */
    public function originalTitle()
    {
        if ($this->main_original_title == "") {
            $this->title_year();
        }
        return $this->main_original_title;
    }

    /** Get year
     * @return string year
     * @see IMDB page / (TitlePage)
     */
    public function year()
    {
        if ($this->main_year == -1) {
            $this->title_year();
        }
        return $this->main_year;
    }

    /** Get end-year
     * if production spanned multiple years, usually for series
     * @return int endyear|null
     * @see IMDB page / (TitlePage)
     */
    public function endyear()
    {
        if ($this->main_endyear == -1) {
            $this->title_year();
        }
        return $this->main_endyear;
    }

    #---------------------------------------------------------------[ Runtime ]---
    /**
     * Retrieve all runtimes and their descriptions
     * @return array<array{time: integer, country: string|null, annotations: array()}>
     * time is the length in minutes, country optionally exists for alternate cuts, annotations is an array of comments
     */
    public function runtime()
    {
        if (empty($this->movieruntimes)) {
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
                $this->movieruntimes[] = array(
                    "time" => $edge->node->seconds / 60,
                    "annotations" => array_map(function ($attribute) {
                        return $attribute->text;
                    }, $edge->node->attributes),
                    "country" => isset($edge->node->country->text) ? $edge->node->country->text : null
                );
            }
        }
        return $this->movieruntimes;
    }

    #----------------------------------------------------------[ Movie Rating ]---
    /**
     * Get movie rating
     * @return int/float or 0
     * @see IMDB page / (TitlePage)
     */
    public function rating()
    {
        if ($this->main_rating == -1) {
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
            if (isset($data->title->ratingsSummary->aggregateRating) && !empty($data->title->ratingsSummary->aggregateRating)) {
                $this->main_rating = $data->title->ratingsSummary->aggregateRating;
            } else {
                $this->main_rating = 0;
            }
        }
        return $this->main_rating;
    }

     /**
     * Return number of votes for this movie
     * @return int or 0
     * @see IMDB page / (TitlePage)
     */
    public function votes()
    {
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
        if (isset($data->title->ratingsSummary->voteCount) && !empty($data->title->ratingsSummary->voteCount)) {
            return $data->title->ratingsSummary->voteCount;
        } else {
            return 0;
        }
    }

    /**
     * Rating out of 100 on metacritic
     * @return int|0
     */
    public function metacritic()
    {
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
        if (isset($data->title->metacritic->metascore->score)) {
            if ($data->title->metacritic !== null) {
                return $data->title->metacritic->metascore->score;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    #-------------------------------------------------------[ Recommendations ]---

    /**
     * Get recommended movies (People who liked this...also liked)
     * @return array<array{title: string, imdbid: number, rating: string| null, img: string|'', year: number|null}>
     * @see IMDB page / (TitlePage)
     */
    public function recommendation()
    {
        if (empty($this->movierecommendations)) {
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
                if (isset($edge->node->primaryImage->url) && $edge->node->primaryImage->url != null) {
                    $fullImageWidth = $edge->node->primaryImage->width;
                    $fullImageHeight = $edge->node->primaryImage->height;
                    $newImageWidth = 140;
                    $newImageHeight = 207;

                    $img = str_replace('.jpg', '', $edge->node->primaryImage->url);

                    $parameter = $this->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                    $thumb = $img . $parameter;

                }
                $this->movierecommendations[] = array(
                    "title" => ucwords($edge->node->titleText->text),
                    "imdbid" => str_replace('tt', '', $edge->node->id),
                    "rating" => isset($edge->node->ratingsSummary->aggregateRating) ? $edge->node->ratingsSummary->aggregateRating : null,
                    "img" => $thumb,
                    "year" => isset($edge->node->releaseYear->year) ? $edge->node->releaseYear->year : null
                );
            }
        }
        return $this->movierecommendations;
    }

    #--------------------------------------------------------[ Language Stuff ]---
    /** Get all spoken languages spoken in this title
     * @return array languages (array[0..n] of strings)
     * @see IMDB page / (TitlePage)
     */
    public function language()
    {
        if (empty($this->langs)) {
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
            if (isset($data->title->spokenLanguages->spokenLanguages)) {
                foreach ($data->title->spokenLanguages->spokenLanguages as $language) {
                    $this->langs[] = $language->text;
                }
            }
            return $this->langs;
        }
    }

    #--------------------------------------------------------------[ Genre(s) ]---
    /** Get all genres the movie is registered for
     * @return array genres (array[0..n] of strings)
     * @see IMDB page / (TitlePage)
     */
    public function genre()
    {
        if (empty($this->moviegenres)) {
            $query = <<<EOF
query Genres(\$id: ID!) {
  title(id: \$id) {
    titleGenres {
      genres {
        genre {
          text
        }
      }
    }
  }
}
EOF;

            $data = $this->graphql->query($query, "Genres", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->titleGenres->genres as $edge) {
                $this->moviegenres[] = $edge->genre->text;
            }
        }
        return $this->moviegenres;
    }

    #--------------------------------------------------------[ Plot (Outline) ]---
    /** Get the main Plot outline for the movie as displayed on top of title page
     * @return string plotoutline
     * @see IMDB page / (TitlePage)
     */
    public function plotoutline()
    {
        if ($this->main_plotoutline == "") {
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
            if (isset($data->title->plot->plotText->plainText)) {
                $this->main_plotoutline = $data->title->plot->plotText->plainText;
            }
        }
        return $this->main_plotoutline;
    }

    #--------------------------------------------------------[ Photo specific ]---
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
        if (isset($data->title->primaryImage->url) && $data->title->primaryImage->url != null) {
            $fullImageWidth = $data->title->primaryImage->width;
            $fullImageHeight = $data->title->primaryImage->height;
            $newImageWidth = 190;
            $newImageHeight = 281;

            $img = str_replace('.jpg', '', $data->title->primaryImage->url);

            $parameter = $this->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            $this->main_poster_thumb = $img . $parameter;

            if (strpos($data->title->primaryImage->url, '._V1')) {
                $this->main_poster = preg_replace('#\._V1_.+?(\.\w+)$#is', '$1', $this->main_poster_thumb);
            }
        }
    }

    /**
     * Calculate The total result parameter and determine if SX or SY is used
     * @parameter $fullImageWidth the width in pixels of the large original image
     * @parameter $fullImageHeight the height in pixels of the large original image
     * @parameter $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @parameter $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @return string example 'QL100_SX190_CR0,15,190,281_.jpg'
     * QL100 = Quality Level, 100 the highest, 0 the lowest quality
     * SX190 = S (scale) X190 desired width
     * CR = Crop (crop left and right, crop top and bottom, New width, New Height)
     * @see IMDB page / (TitlePage)
     */
    private function resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight)
    {
        // original source aspect ratio
        $ratio_orig = $fullImageWidth / $fullImageHeight;

        // new aspect ratio
        $ratio_new = $newImageWidth / $newImageHeight;

        // check if the image must be treated as SX or SY
        if ($ratio_new < $ratio_orig) {
            $cropParameter = $this->thumbUrlCropParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            return 'QL75_SY' . $newImageHeight . '_CR' . $cropParameter . ',0,' . $newImageWidth . ',' . $newImageHeight . '_.jpg';
        } else {
            $cropParameter = $this->thumbUrlCropParameterVertical($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            return 'QL75_SX' . $newImageWidth . '_CR0,' . $cropParameter . ',' . $newImageWidth .',' . $newImageHeight . '_.jpg';
        }
    }

    /**
     * Calculate if cropValue has to be round to previous or next even integer
     * @parameter $totalPixelCropSize how much pixels in total need to be cropped
     */
    private function roundInteger($totalPixelCropSize)
    {
        if ((($totalPixelCropSize - floor($totalPixelCropSize)) < 0.5)) {
            // Previous even integer
            $num = 2 * round($totalPixelCropSize / 2.0);
        } else {
            // Next even integer
            $num = ceil($totalPixelCropSize);
            $num += $num % 2;
        }
        return $num;
    }

    /**
     * Calculate HORIZONTAL (left and right) crop value for primary, cast, episode, recommendations and mainphoto images
     * Output is for portrait images!
     * @parameter $fullImageWidth the width in pixels of the large original image
     * @parameter $fullImageHeight the height in pixels of the large original image
     * @parameter $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @parameter $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @see IMDB page / (TitlePage)
     */
    private function thumbUrlCropParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight)
    {
        $newScalefactor = $fullImageHeight / $newImageHeight;
        $scaledWidth = $fullImageWidth / $newScalefactor;
        $totalPixelCropSize = $scaledWidth - $newImageWidth;
        $cropValue = max($this->roundInteger($totalPixelCropSize)/2, 0);
        return $cropValue;
    }

    /**
     * Calculate VERTICAL (Top and bottom)crop value for primary, cast, episode and recommendations images
     * Output is for landscape images!
     * @parameter $fullImageWidth the width in pixels of the large original image
     * @parameter $fullImageHeight the height in pixels of the large original image
     * @parameter $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @parameter $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @see IMDB page / (TitlePage)
     */
    private function thumbUrlCropParameterVertical($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight)
    {
        $newScalefactor = $fullImageWidth / $newImageWidth;
        $scaledHeight = $fullImageHeight / $newScalefactor;
        $totalPixelCropSize = $scaledHeight - $newImageHeight;
        $cropValue = max($this->roundInteger($totalPixelCropSize)/2, 0);
        return $cropValue;
    }

    /**
     * Get the main photo image url for thumbnail or full size
     * @param boolean $thumb get the thumbnail (height: 281) or the full version
     * @return string|false photo (string URL if found, FALSE otherwise)
     * @see IMDB page / (TitlePage)
     */
    public function photo($thumb = true)
    {
        if (empty($this->main_poster)) {
            $this->populatePoster();
        }
        if (!$thumb && empty($this->main_poster)) {
            return false;
        }
        if ($thumb && empty($this->main_poster_thumb)) {
            return false;
        }
        if ($thumb) {
            return $this->main_poster_thumb;
        }
        return $this->main_poster;
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
            if ($data->title->countriesOfOrigin != null) {
                foreach ($data->title->countriesOfOrigin->countries as $country) {
                    $this->countries[] = $country->text;
                }
            }
        }
        return $this->countries;
    }

    #------------------------------------------------------------[ Movie AKAs ]---
    /**
     * Get movie's alternative names
     * The first item in the list will be the original title
     * @return array<array{title: string, country: string, comment: array()}>
     * Ordered Ascending by Country
     * @see IMDB page ReleaseInfo
     */
    public function alsoknow()
    {
        if (empty($this->akas)) {
            $query = <<<EOF
query AlsoKnow(\$id: ID!) {
  title(id: \$id) {
    originalTitleText {
      text
    }
    akas(first: 9999, sort: {order: ASC by: COUNTRY}) {
      edges {
        node {
          country {
            text
          }
          displayableProperty {
            value {
              plainText
            }
          }
          attributes {
            text
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "AlsoKnow", ["id" => "tt$this->imdbID"]);
            $originalTitle = $data->title->originalTitleText->text;
            $comments = array();
            if (!empty($originalTitle)) {
                $this->akas[] = array(
                    "title" => ucwords($originalTitle),
                    "country" => "(Original Title)",
                    "comment" => $comments
                );
            }

            foreach ($data->title->akas->edges as $edge) {
                foreach ($edge->node->attributes as $attribute) {
                    if (isset($attribute->text) && $attribute->text != '') {
                        $comments[] = $attribute->text;
                    }
                }
                $this->akas[] = array(
                    "title" => ucwords($edge->node->displayableProperty->value->plainText),
                    "country" => isset($edge->node->country->text) ? ucwords($edge->node->country->text) : 'Unknown',
                    "comment" => $comments
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
query Mpaa(\$id: ID!) {
  title(id: \$id) {
    certificates(first: 9999) {
      edges {
        node {
          country {
            text
          }
          rating
          attributes {
            text
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Mpaa", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->certificates->edges as $edge) {
                $comments = array();
                foreach ($edge->node->attributes as $key => $attribute) {
                    if (isset($attribute->text) && $attribute->text != '') {
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
        if ($this->main_top250 == -1) {
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
                $this->main_top250 = $data->title->ratingsSummary->topRanking->rank;
            } else {
                $this->main_top250 = 0;
            }
        }
        return $this->main_top250;
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
    * @return array principal_credits[category][Director, Writer, Creator, Stars] (array[0..n] of array[name,imdbid])
    * Not all categories are always available, TV series has Creator instead of writer
    */
    public function principalCredits()
    {
        if (empty($this->principal_credits)) {
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
                $category = '';
                $cat = $value->credits[0]->category->text;
                if ($cat == "Actor" || $cat == "Actress") {
                    $category = "Star";
                } else {
                    $category = $value->credits[0]->category->text;
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
                $this->principal_credits[$category] = $temp;
            }
        }
        return $this->principal_credits;
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
        if (!empty($this->credits_cast)) {
            return $this->credits_cast;
        }
        $filter = ', filter: { categories: ["cast"] }';
$queryNode = <<<EOF
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
        $data = $this->graphQlGetAll("CreditQuery", "credits", $queryNode, $filter);
        foreach ($data as $edge) {
            $name = isset($edge->node->name->nameText->text) ? $edge->node->name->nameText->text : '';
            $imdb = isset($edge->node->name->id) ? str_replace('nm', '', $edge->node->name->id) : '';
            
            // character
            $castCharacters = array();
            if ($edge->node->characters != null) {
                foreach ($edge->node->characters as $keyCharacters => $character) {
                    $castCharacters[] = $character->name;
                }
            }
            
            // comment, name_alias and credited
            $comments = array();
            $name_alias = null;
            $credited = true;
            if ($edge->node->attributes != null) {
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
            if (isset($edge->node->name->primaryImage->url) && $edge->node->name->primaryImage->url != null) {
                $fullImageWidth = $edge->node->name->primaryImage->width;
                $fullImageHeight = $edge->node->name->primaryImage->height;
                $newImageWidth = 32;
                $newImageHeight = 44;

                $img = str_replace('.jpg', '', $edge->node->name->primaryImage->url);

                $parameter = $this->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                $imgUrl = $img . $parameter;
            }
            
            $this->credits_cast[] = array(
                'imdb' => $imdb,
                'name' => $name,
                'name_alias' => $name_alias,
                'credited' => $credited,
                'character' => $castCharacters,
                'comment' => $comments,
                'thumb' => $imgUrl
            );
        }
        return $this->credits_cast;
    }

    #------------------------------------------------------[ Helper: Crew Category ]---
    /** create query and fetch data from category
     * @param string $crewCategory (producer, writer, composer or director)
     * @return array data (array[0..n] of objects)
     * @see used by the methods director, writer, producer, composer
     */
    private function creditsQuery($crewCategory)
    {
$query = <<<EOF
query CreditCrew(\$id: ID!) {
  title(id: \$id) {
    credits(first: 9999, filter: { categories: ["$crewCategory"] }) {
      edges {
        node {
          name {
            nameText {
              text
            }
            id
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
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "CreditCrew", ["id" => "tt$this->imdbID"]);
        return $data;
    }

    #-------------------------------------------------------------[ Helper: Director / Composer ]---
    /**
     * process data for Director and Composer
     * @return array director/composer (array[0..n] of arrays[imdb,name,role])
     * @see used by the methods director and composer
     */
    private function directorComposer($data)
    {
        $output = array();
        foreach ($data->title->credits->edges as $edge) {
            $name = isset($edge->node->name->nameText->text) ? $edge->node->name->nameText->text : '';
            $imdb = isset($edge->node->name->id) ? str_replace('nm', '', $edge->node->name->id) : '';
            $role = '';
            if ($edge->node->attributes != NULL && count($edge->node->attributes) > 0) {
                foreach ($edge->node->attributes as $keyAttributes => $attribute) {
                    $role .= '(' . $attribute->text . ')';
                    if ($keyAttributes !== key(array_slice($edge->node->attributes, -1, 1, true))) {
                        $role .= ' ';
                    }
                }
            }
            if ($edge->node->episodeCredits != NULL && count($edge->node->episodeCredits->edges) > 0) {
                $totalEpisodes = count($edge->node->episodeCredits->edges);
                if ($totalEpisodes == 1) {
                    $value =  $edge->node->episodeCredits->edges[0]->node->title->series->displayableEpisodeNumber->episodeNumber->text;
                    if ($value == "unknown") {
                        $totalEpisodes = 'unknown';
                    }
                }
                $episodeText = ' episode';
                if ($totalEpisodes > 1) {
                    $episodeText .= 's';
                }
                if ($edge->node->attributes != NULL && count($edge->node->attributes) > 0) {
                    $role .= ' ';
                }
                $role .= '(' . $totalEpisodes . $episodeText;
                if ($edge->node->episodeCredits->yearRange != NULL && isset($edge->node->episodeCredits->yearRange->year)) {
                    $role .= ', ' .$edge->node->episodeCredits->yearRange->year;
                    if (isset($edge->node->episodeCredits->yearRange->endYear)) {
                        $role .= '-' . $edge->node->episodeCredits->yearRange->endYear;
                    }
                }
                $role .= ')';
            }
            $output[] = array(
                'imdb' => $imdb,
                'name' => $name,
                'role' => $role
            );
        }
        return $output;
    }

    #-------------------------------------------------------------[ Directors ]---
    /**
     * Get the director(s) of the movie
     * @return array director (array[0..n] of arrays[imdb,name,role])
     * @see IMDB page /fullcredits
     */
    public function director()
    {
        if (!empty($this->credits_director)) {
            return $this->credits_director;
        }
        $directorData = $this->directorComposer($this->creditsQuery("director"));
        
        return $this->credits_director = $directorData;
    }

    #---------------------------------------------------------------[ Writers ]---
    /** Get the writer(s)
     * @return array writers (array[0..n] of arrays[imdb,name,role])
     * @see IMDB page /fullcredits
     */
    public function writer()
    {
        if (!empty($this->credits_writer)) {
            return $this->credits_writer;
        }
        $data = $this->creditsQuery("writer");
        foreach ($data->title->credits->edges as $edge) {
            $name = isset($edge->node->name->nameText->text) ? $edge->node->name->nameText->text : '';
            $imdb = isset($edge->node->name->id) ? str_replace('nm', '', $edge->node->name->id) : '';
            $role = '';
            if ($edge->node->jobs != NULL && count($edge->node->jobs) > 0) {
                foreach ($edge->node->jobs as $keyJobs => $job) {
                    $role = '';
                    if ($edge->node->attributes != NULL && count($edge->node->attributes) > 0) {
                        $role .= '(' . $edge->node->attributes[$keyJobs]->text . ')';
                    }
                    if ($edge->node->episodeCredits != NULL && count($edge->node->episodeCredits->edges) > 0) {
                        if ($keyJobs == 0) {
                            $totalEpisodes = count($edge->node->episodeCredits->edges);
                            if ($totalEpisodes == 1) {
                                $value =  $edge->node->episodeCredits->edges[0]->node->title->series->displayableEpisodeNumber->episodeNumber->text;
                                if ($value == "unknown") {
                                    $totalEpisodes = 'unknown';
                                }
                            }
                            $episodeText = ' episode';
                            if ($totalEpisodes > 1) {
                                $episodeText .= 's';
                            }
                            if ($edge->node->attributes != NULL && count($edge->node->attributes) > 0) {
                                $role .= ' ';
                            }
                            $role .= '(' . $totalEpisodes . $episodeText;
                            if ($edge->node->episodeCredits->yearRange != NULL && isset($edge->node->episodeCredits->yearRange->year)) {
                                $role .= ', ' .$edge->node->episodeCredits->yearRange->year;
                                if (isset($edge->node->episodeCredits->yearRange->endYear)) {
                                    $role .= '-' . $edge->node->episodeCredits->yearRange->endYear;
                                }
                            }
                            $role .= ')';
                        } else {
                            $role .= ' (unknown episodes';
                            if ($edge->node->episodeCredits->yearRange != NULL && isset($edge->node->episodeCredits->yearRange->year)) {
                                $role .= ', ' .$edge->node->episodeCredits->yearRange->year;
                                if (isset($edge->node->episodeCredits->yearRange->endYear)) {
                                    $role .= '-' . $edge->node->episodeCredits->yearRange->endYear;
                                }
                            }
                            $role .= ')';
                        }
                    }
                    $this->credits_writer[] = array(
                        'imdb' => $imdb,
                        'name' => $name,
                        'role' => $role
                    );
                }
            } else {
                $this->credits_writer[] = array(
                        'imdb' => $imdb,
                        'name' => $name,
                        'role' => $role
                    );
            }
        }
        return $this->credits_writer;
    }

    #---------------------------------------------------------------[ Producers ]---
    /** Get the producers(s)
     * @return array producers (array[0..n] of arrays[imdb,name,role])
     * @see IMDB page /fullcredits
     */
    public function producer()
    {
        if (!empty($this->credits_producer)) {
            return $this->credits_producer;
        }
        $data = $this->creditsQuery("producer");
        foreach ($data->title->credits->edges as $edge) {
            $name = isset($edge->node->name->nameText->text) ? $edge->node->name->nameText->text : '';
            $imdb = isset($edge->node->name->id) ? str_replace('nm', '', $edge->node->name->id) : '';
            $role = '';
            if ($edge->node->jobs != NULL && count($edge->node->jobs) > 0) {
                foreach ($edge->node->jobs as $keyJobs => $job) {
                    $role .= $job->text;
                    if ($keyJobs !== key(array_slice($edge->node->jobs, -1, 1, true))) {
                        $role .= ' / ';
                    }
                }
            }
            if ($edge->node->attributes != NULL && count($edge->node->attributes) > 0) {
                $countAttributes = count($edge->node->attributes);
                $countJobs = count($edge->node->jobs);
                if ($countAttributes > $countJobs) {
                    $key = ($countJobs);
                    $role .= ' ';
                    foreach ($edge->node->attributes as $keyAttributes => $attribute) {
                        if ($keyAttributes >= $key) {
                            $role .= '(' . $attribute->text . ')';
                            if ($keyAttributes !== key(array_slice($edge->node->attributes, -1, 1, true))) {
                                $role .= ' ';
                            }
                        }
                    }
                }
            }
            if ($edge->node->episodeCredits != NULL && count($edge->node->episodeCredits->edges) > 0) {
                $totalEpisodes = count($edge->node->episodeCredits->edges);
                if ($totalEpisodes == 1) {
                    $value =  $edge->node->episodeCredits->edges[0]->node->title->series->displayableEpisodeNumber->episodeNumber->text;
                    if ($value == "unknown") {
                        $totalEpisodes = 'unknown';
                    }
                }
                $episodeText = ' episode';
                if ($totalEpisodes > 1) {
                    $episodeText .= 's';
                }
                if ($edge->node->attributes != NULL && count($edge->node->attributes) > 0) {
                    $role .= ' ';
                }
                $role .= '(' . $totalEpisodes . $episodeText;
                if ($edge->node->episodeCredits->yearRange != NULL && isset($edge->node->episodeCredits->yearRange->year)) {
                    $role .= ', ' .$edge->node->episodeCredits->yearRange->year;
                    if (isset($edge->node->episodeCredits->yearRange->endYear)) {
                        $role .= '-' . $edge->node->episodeCredits->yearRange->endYear;
                    }
                }
                $role .= ')';
            }
            $this->credits_producer[] = array(
                'imdb' => $imdb,
                'name' => $name,
                'role' => $role
            );
        }
        return $this->credits_producer;
    }

    #-------------------------------------------------------------[ Composers ]---
    /** Obtain the composer(s) ("Original Music by...")
     * @return array composer (array[0..n] of arrays[imdb,name,role])
     * @see IMDB page /fullcredits
     */
    public function composer()
    {
        if (!empty($this->credits_composer)) {
            return $this->credits_composer;
        }
        $composerData = $this->directorComposer($this->creditsQuery("composer"));
        return $this->credits_composer = $composerData;
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
        if (empty($this->crazy_credits)) {
            $query = <<<EOF
query CrazyCredits(\$id: ID!) {
  title(id: \$id) {
    crazyCredits(first: 9999) {
      edges {
        node {
          displayableArticle {
            body {
              plainText
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "CrazyCredits", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->crazyCredits->edges as $edge) {
                $crazyCredit = isset($edge->node->displayableArticle->body->plainText) ? $edge->node->displayableArticle->body->plainText : '';
                $this->crazy_credits[] = $crazyCredit;
            }
        }
        return $this->crazy_credits;
    }

    #========================================================[ /episodes page ]===
    #--------------------------------------------------------[ Season/Year check ]---
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
        if ($seasonsData->title->episodes != null) {
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

    #--------------------------------------------------------[ Episodes Array ]---
    /**
     * Get the series episode(s)
     * @return array episodes (array[0..n] of array[0..m] of array[imdbid,title,airdate,plot,season,episode,image_url])
     * array(1) {
        [1]=>
        array(13) {
            [1]=> //can be seasonnumber, year or -1 (Unknown)
            array(6) {
            ["imdbid"]=>
            string(7) "1495166"
            ["title"]=>
            string(5) "Pilot"
            ["airdate"]=>
            string(11) "7 jun. 2010"
            ["plot"]=>
            string(648) "Admirably unselfish fireman Joe Tucker takes charge when he and six others..
            ["episode"]=>
            string(1) "1" //can be seasonnumber or -1 (Unknown)
            ["image_url"]=>
            string(108) "https://m.media-amazon.com/images/M/MV5BMjM3NjI2MDA2OF5BMl5BanBnXkFtZTgwODgwNjEyMjE@._V1_UY126_UX224_AL_.jpg"
            }
        }
     * @see IMDB page /episodes
     * @param $yearbased This gives user control if episodes are yearbased or season based
     * @version The outer array keys reflects the real season seasonnumber! Episodes can start at 0 (pilot episode)
     * @see there seems to be a limit on max episodes per season of 250!
     *      This may also be true for year based tv series, so max 250 per year!
     */
    public function episode($yearbased = 0)
    {
        if ($this->movietype() === "TV Series" || $this->movietype() === "TV Mini Series") {
            if (empty($this->season_episodes)) {
                // Check if season or year based
                $seasonsData = $this->seasonYearCheck($yearbased);
                if ($seasonsData == null) {
                    return $this->season_episodes;
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
//Episode Query
                    $queryEpisodes = <<<EOF
query Episodes(\$id: ID!) {
  title(id: \$id) {
    primaryImage {
      url
    }
    episodes {
      episodes(first: 9999, $filter) {
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
      }
    }
  }
}
EOF;
                    $episodesData = $this->graphql->query($queryEpisodes, "Episodes", ["id" => "tt$this->imdbID"]);
                    $episodes = array();
                    foreach ($episodesData->title->episodes->episodes->edges as $keyEp => $edge) {
                        // vars
                        $imdbId = '';
                        $title = '';
                        $airDate = '';
                        $plot = '';
                        $epNumber = '';
                        $imgUrl = '';
                        // Episode ImdbId
                        $imdbId = isset($edge->node->id) ? str_replace('tt', '', $edge->node->id) : '';
                        // Episode Title
                        $title = isset($edge->node->titleText->text) ? $edge->node->titleText->text : '';
                        // Episode Airdate
                        $day = isset($edge->node->releaseDate->day) ? $edge->node->releaseDate->day : '';
                        $month = isset($edge->node->releaseDate->month) ? $edge->node->releaseDate->month : '';
                        $year = isset($edge->node->releaseDate->year) ? $edge->node->releaseDate->year : '';
                        // return airdate like shown on episode as string.
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
                        if (isset($edge->node->primaryImage->url) && !empty($edge->node->primaryImage->url)) {
                            $epImageUrl = $edge->node->primaryImage->url;
                            $fullImageWidth = $edge->node->primaryImage->width;
                            $fullImageHeight = $edge->node->primaryImage->height;
                            $newImageWidth = 224;
                            $newImageHeight = 126;

                            $img = str_replace('.jpg', '', $epImageUrl);

                            $parameter = $this->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                            $imgUrl = $img . $parameter;

                        }
                        $episode = array(
                                'imdbid' => $imdbId,
                                'title' => $title,
                                'airdate' => $airDate,
                                'plot' => $plot,
                                'season' => $seasonYear,
                                'episode' => $epNumber,
                                'image_url' => $imgUrl
                            );
                        $episodes[] = $episode;
                    }
                    $this->season_episodes[$seasonYear] = $episodes;
                }
            }
        }
        return $this->season_episodes;
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
        if (isset($data->title->episodes) && $data->title->episodes != null) {
            $this->isOngoing = isset($data->title->episodes->isOngoing) ? $data->title->episodes->isOngoing : null;
        }
        return $this->isOngoing;
    }

    #===========================================================[ /goofs page ]===
    #-----------------------------------------------------------[ Goofs Array ]---
    /** Get the goofs
     * @return array goofs (array[0..n] of array[type,content]
     * @see IMDB page /goofs
     * @version Spoilers are currently skipped (differently formatted)
     */
    public function goof()
    {
        if (empty($this->goofs)) {
            $query = <<<EOF
query Goofs(\$id: ID!) {
  title(id: \$id) {
    goofs(first: 9999, filter: {spoilers: EXCLUDE_SPOILERS}) {
      edges {
        node {
          category {
            text
          }
          displayableArticle {
            body {
              plainText
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Goofs", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->goofs->edges as $edge) {
                $type = isset($edge->node->category->text) ? $edge->node->category->text : '';
                $content = isset($edge->node->displayableArticle->body->plainText) ? $edge->node->displayableArticle->body->plainText : '';
                $this->goofs[] = array(
                    "type" => $type,
                    "content" => $content
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
        if (empty($this->moviequotes)) {
            $query = <<<EOF
query Quotes(\$id: ID!) {
  title(id: \$id) {
    quotes(first: 9999) {
      edges {
        node {
          displayableArticle {
            body {
              plaidHtml
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Quotes", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->quotes->edges as $key => $edge) {
                $quoteParts = explode("<li>", $edge->node->displayableArticle->body->plaidHtml);
                foreach ($quoteParts as $quoteItem) {
                    if (trim(strip_tags($quoteItem)) == '') {
                        continue;
                    }
                    $this->moviequotes[$key][] = trim(strip_tags($quoteItem));
                }
            }
            
        }
        return $this->moviequotes;
    }

    #==========================================================[ /trivia page ]===
    /**
     * Get the trivia info
     * @param boolean $spoil if true spoilers are also included.
     * @return array trivia (array[0..n] string
     * @see IMDB page /trivia
     */
    public function trivia($spoil = false)
    {
        if (empty($this->trivia)) {
            if ($spoil === false) {
                $filter = 'first: 9999, filter: {spoilers: EXCLUDE_SPOILERS}';
            } else {
                $filter = 'first: 9999';
            }
            $query = <<<EOF
query Trivia(\$id: ID!) {
  title(id: \$id) {
    trivia($filter) {
      edges {
        node {
          displayableArticle {
            body {
              plainText
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Trivia", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->trivia->edges as $edge) {
                $this->trivia[] = preg_replace('/\s\s+/', ' ', $edge->node->displayableArticle->body->plainText);
            }
        }
        return $this->trivia;
    }

    #======================================================[ Soundtrack ]===
    /**
     * Get the soundtrack listing
     * @return array soundtracks
     * [ soundtrack : name of the soundtrack
     *   array credits : every credit line about writer, performer, etc
     * ]
     * @see IMDB page /soundtrack
     */
    public function soundtrack()
    {
        if (empty($this->soundtracks)) {
            $query = <<<EOF
query Soundtrack(\$id: ID!) {
  title(id: \$id) {
    soundtrack(first: 9999) {
      edges {
        node {
          text
          comments {
            plainText
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Soundtrack", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->soundtrack->edges as $edge) {
                $title = '';
                if (isset($edge->node->text) && $edge->node->text !== '') {
                    $title = ucwords(strtolower(trim($edge->node->text)), " (");
                } else {
                    $title = 'Unknown';
                }
                $credits = array();
                if (isset($edge->node->comments) && $edge->node->comments !== '') {
                    foreach ($edge->node->comments as $key => $comment) {
                        if (trim(strip_tags($comment->plainText)) !== '') {
                            $credits[] = $comment->plainText;
                        }
                    }
                }
                $this->soundtracks[] = array(
                        'soundtrack' => $title,
                        'credits' => $credits
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
query FilmingLocations(\$id: ID!) {
  title(id: \$id) {
    filmingLocations(first: 9999) {
      edges {
        node {
          text
          displayableProperty {
            qualifiersInMarkdownList {
              markdown
            }
          }
        }
      }
    }
  }
}
EOF;

            $data = $this->graphql->query($query, "FilmingLocations", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->filmingLocations->edges as $edge) {
                $real = isset($edge->node->text) ? $edge->node->text : '';
                $movie = array();
                if ($edge->node->displayableProperty->qualifiersInMarkdownList != null) {
                    foreach ($edge->node->displayableProperty->qualifiersInMarkdownList as $key => $attribute) {
                        $movie[] = $attribute->markdown;
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
    /**
     * Fetch all company credits
     * @param string $category e.g. distribution, production
     * @return array<array{name: string, id: string, country: string, attribute: string, year: string}>
     */
    protected function companyCredits($category)
    {
        $query = <<<EOF
query CompanyCredits(\$id: ID!) {
  title(id: \$id) {
    companyCredits(first: 9999, filter: {categories: ["$category"]}) {
      edges {
        node {
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
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "CompanyCredits", ["id" => "tt$this->imdbID"]);
        $results = array();
        foreach ($data->title->companyCredits->edges as $edge) {
            $companyId = isset($edge->node->company->id) ? str_replace('co', '', $edge->node->company->id ) : '';
            $companyName = isset($edge->node->displayableProperty->value->plainText) ? $edge->node->displayableProperty->value->plainText : '';
            $companyCountry = '';
            if ($edge->node->countries != null && isset($edge->node->countries[0]->text)) {
                $companyCountry = $edge->node->countries[0]->text;
            }
            $companyAttribute = array();
            if ($edge->node->attributes != null) {
                foreach ($edge->node->attributes as $key => $attribute) {
                    $companyAttribute[] = $attribute->text;
                }
            }
            $companyYear = '';
            if ($edge->node->yearsInvolved != null && isset($edge->node->yearsInvolved->year)) {
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

    #---------------------------------------------------[ Producing Companies ]---

    /** Info about Production Companies
     * @return array<array{name: string, id: string, country: string, attribute: string, year: int}>
     * @see IMDB page /companycredits
     */
    public function prodCompany()
    {
        if (empty($this->compcred_prod)) {
            $this->compcred_prod = $this->companyCredits("production");
        }
        return $this->compcred_prod;
    }

    #------------------------------------------------[ Distributing Companies ]---

    /** Info about distributors
     * @return array<array{name: string, id: string, country: string, attribute: string, year: int}>
     * @see IMDB page /companycredits
     */
    public function distCompany()
    {
        if (empty($this->compcred_dist)) {
            $this->compcred_dist = $this->companyCredits("distribution");
        }
        return $this->compcred_dist;
    }

    #---------------------------------------------[ Special Effects Companies ]---

    /** Info about Special Effects companies
     * @return array<array{name: string, id: string, country: string, attribute: string, year: int}>
     * @see IMDB page /companycredits
     */
    public function specialCompany()
    {
        if (empty($this->compcred_special)) {
            $this->compcred_special = $this->companyCredits("specialEffects");
        }
        return $this->compcred_special;
    }

    #-------------------------------------------------------[ Other Companies ]---

    /** Info about other companies
     * @return array<array{name: string, id: string, country: string, attribute: string, year: int}>
     * @see IMDB page /companycredits
     */
    public function otherCompany()
    {
        if (empty($this->compcred_other)) {
            $this->compcred_other = $this->companyCredits("miscellaneous");
        }
        return $this->compcred_other;
    }

    #========================================================[ /Box Office page ]===
    #-------------------------------------------------------[ productionBudget ]---
    /** Info about productionBudget
     * @return production_budget: array[amount, currency]>
     * @see IMDB page /title
     */
    public function budget()
    {
        if (empty($this->production_budget)) {
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
            if ($data->title->productionBudget != null && isset($data->title->productionBudget->budget->amount)) {
                $this->production_budget["amount"] = $data->title->productionBudget->budget->amount;
                $this->production_budget["currency"] = $data->title->productionBudget->budget->currency;
            } else {
                return $this->production_budget;
            }
        }
        return $this->production_budget;
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
                if (isset($edge->node->boxOfficeAreaType->text) && $edge->node->boxOfficeAreaType->text != '') {
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
        if (empty($this->all_keywords)) {
            $query = <<<EOF
query Keywords(\$id: ID!) {
  title(id: \$id) {
    keywords(first: 9999) {
      edges {
        node {
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

            $data = $this->graphql->query($query, "Keywords", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->keywords->edges as $edge) {
                $this->all_keywords[] = $edge->node->keyword->text->text;
            }
        }
        return $this->all_keywords;
    }

    #========================================================[ /Alternate versions page ]===
    /**
     * Get the Alternate Versions for a given movie
     * @return array Alternate Version (array[0..n] of string)
     * @see IMDB page /alternateversions
     */
    public function alternateVersion()
    {
        if (empty($this->moviealternateversions)) {
            $query = <<<EOF
query AlternateVersions(\$id: ID!) {
  title(id: \$id) {
    alternateVersions(first: 9999) {
      edges {
        node {
          text {
            plainText
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "AlternateVersions", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->alternateVersions->edges as $edge) {
                $this->moviealternateversions[] = $edge->node->text->plainText;
            }
        }
        return $this->moviealternateversions;
    }

    #-------------------------------------------------[ Main images ]---
    /**
     * Get image URLs for (default 6) pictures from photo page
     * @param $amount, int for how many images, max = 9999
     * @return array [0..n] of string image source
     */
    public function mainphoto($amount = 6)
    {
        if (empty($this->main_photo)) {
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
                if (isset($edge->node->url) && $edge->node->url != '') {
                    $fullImageWidth = $edge->node->width;
                    $fullImageHeight = $edge->node->height;
                    // calculate crop value
                    $cropParameter = $this->thumbUrlCropParameter($fullImageWidth, $fullImageHeight, 100, 100);

                    $imgUrl = str_replace('.jpg', '', $edge->node->url);

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
                    $this->main_photo[] = $imgUrl . 'QL75_S' . $orientation . '100_CR' . $cropParameter . ',0,100,100_AL_.jpg';

                }
            }
        }
        return $this->main_photo;
    }
    
    #-------------------------------------------------[ Trailer ]---
    /**
     * Get video URL's and images from videogallery page (Trailers only)
     * @return array trailers (array[string videoUrl,string videoImageUrl])
     * videoUrl is a embeded url that is tested to work in iframe (won't work in html5 <video>)
     */
    public function trailer()
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
                if (isset($edge->node->thumbnail->url) && $edge->node->thumbnail->url != '') {
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
                if (count($this->trailers) <= 1) {
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
     * @return array main_awards (array[award|string, nominations|int, wins|int])
     * @see IMDB page / (TitlePage)
     */
    public function mainaward()
    {
        if (empty($this->main_awards)) {
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
            $this->main_awards['award'] = '';
            $this->main_awards['nominations'] = '';
            $this->main_awards['wins'] = '';
            if (isset($data->title->prestigiousAwardSummary) && $data->title->prestigiousAwardSummary !== null) {
                $this->main_awards['award'] = $data->title->prestigiousAwardSummary->award->text;
                $this->main_awards['nominations'] = $data->title->prestigiousAwardSummary->nominations;
                $this->main_awards['wins'] = $data->title->prestigiousAwardSummary->wins;
            }
        }
        return $this->main_awards;
    }

    #========================================================[ Helper functions ]===  
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

        // Results are paginated, so loop until we've got all the data
        $endCursor = null;
        $hasNextPage = true;
        $edges = array();
        while ($hasNextPage) {
            $data = $this->graphql->query($query, $queryName, ["id" => "tt$this->imdbID", "after" => $endCursor]);
            $edges = array_merge($edges, $data->title->{$fieldName}->edges);
            $hasNextPage = $data->title->{$fieldName}->pageInfo->hasNextPage;
            $endCursor = $data->title->{$fieldName}->pageInfo->endCursor;
        }
        return $edges;
    }
}
