<?php
#############################################################################
# IMDBPHP                              (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * A title on IMDb
 * @author Georgos Giagas
 * @author Izzy (izzysoft AT qumran DOT org)
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
    protected $credits_writing = array();
    protected $langs = array();
    protected $all_keywords = array();
    protected $main_poster = "";
    protected $main_poster_thumb = "";
    protected $main_plotoutline = "";
    protected $main_movietype = "";
    protected $main_title = "";
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
    protected $soundtracks = array();
    protected $taglines = array();
    protected $trivia = array();
    protected $locations = array();
    protected $moviealternateversions = array();

    protected $pageUrls = array(
        "AlternateVersions" => '/alternateversions',
        "Credits" => "/fullcredits",
        "Episodes" => "/episodes",
        "Keywords" => "/keywords",
        "Locations" => "/locations",
        "ParentalGuide" => "/parentalguide",
        "Plot" => "/plotsummary",
        "Quotes" => "/quotes",
        "ReleaseInfo" => "/releaseinfo",
        "Soundtrack" => "/soundtrack",
        "Taglines" => "/taglines",
        "Technical" => "/technical",
        "Video" => "/videogallery/content_type-trailer",
        "Mediaindex" => "/mediaindex",
        "Title" => "/",
        "Trivia" => "/trivia",
    );

    /**
     * Create an imdb object populated with id, title, year, and movie type
     * @param string $id imdb ID
     * @param string $title film title
     * @param int $year
     * @param string $type
     * @param Config $config
     * @return Title
     */
    public static function fromSearchResult(
        $id,
        $title,
        $year,
        $type,
        Config $config = null
    ) {
        $imdb = new Title($id, $config);
        $imdb->main_title = $title;
        $imdb->main_year = (int)$year;
        $imdb->main_movietype = $type;
        return $imdb;
    }

    /**
     * @param string $id IMDb ID. e.g. 285331 for https://www.imdb.com/title/tt0285331/
     * @param Config $config OPTIONAL override default config
     */
    public function __construct(
        $id,
        Config $config = null
    ) {
        parent::__construct($config);
        $this->setid($id);
    }

    #-------------------------------------------------------------[ Open Page ]---

    protected function buildUrl($page = null)
    {
        return "https://" . $this->imdbsite . "/title/tt" . $this->imdbID . $this->getUrlSuffix($page);
    }

    /**
     * @param string $pageName internal name of the page
     * @return string
     */
    protected function getUrlSuffix($pageName)
    {
        if (isset($this->pageUrls[$pageName])) {
            return $this->pageUrls[$pageName];
        }

        if (preg_match('!^Episodes-(-?\d+)$!', $pageName, $match)) {
            if (strlen($match[1]) == 4) {
                return '/episodes?year=' . $match[1];
            } else {
                return '/episodes?season=' . $match[1];
            }
        }
    }

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
        $this->main_movietype = isset($data->title->titleType->text) ? $data->title->titleType->text : '';
        $this->main_year = isset($data->title->releaseYear->year) ? $data->title->releaseYear->year : '';
        $this->main_endyear = isset($data->title->releaseYear->endYear) ? $data->title->releaseYear->endYear : null;
        if ($this->main_year == "????") {
            $this->main_year = "";
        }
    }

    /** Get movie type
     * @return string movietype (TV Series, Movie, TV Episode, TV Special, TV Movie, TV Mini-Series, Video Game, TV Short, Video)
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
     * @return array<array{time: integer, country: string|null, annotations: string[]}>
     * time is the length in minutes, country optionally exists for alternate cuts, annotations is an array of comments meant to describe this cut
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
     * @return float rating current rating as given by IMDB site
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
                $this->movierecommendations[] = array(
                    "title" => ucwords($edge->node->titleText->text),
                    "imdbid" => str_replace('tt', '', $edge->node->id),
                    "rating" => isset($edge->node->ratingsSummary->aggregateRating) ? $edge->node->ratingsSummary->aggregateRating : null,
                    "img" => isset($edge->node->primaryImage->url) ? $edge->node->primaryImage->url : '',
                    "year" => isset($edge->node->releaseYear->year) ? $edge->node->releaseYear->year : null
                );
            }
        }
        return $this->movierecommendations;
    }

    #--------------------------------------------------------[ Language Stuff ]---
    /** Get all languages this movie is available in
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
    /** Get the main Plot outline for the movie
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
    }
  }
}
EOF;

        $data = $this->graphql->query($query, "Poster", ["id" => "tt$this->imdbID"]);

        if (isset($data->title->primaryImage->url) && $data->title->primaryImage->url != null) {
            $this->main_poster_thumb = $data->title->primaryImage->url;
            if (strpos($data->title->primaryImage->url, '._V1')) {
                $this->main_poster = preg_replace('#\._V1_.+?(\.\w+)$#is', '$1', $this->main_poster_thumb);
            }
        }
    }

    /**
     * Get the poster/cover image URL
     * @param boolean $thumb get the thumbnail (182x268) or the full sized image
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
            foreach ($data->title->countriesOfOrigin->countries as $country) {
                $this->countries[] = $country->text;
            }
        }
        return $this->countries;
    }

    #------------------------------------------------------------[ Movie AKAs ]---
    /**
     * Get movie's alternative names
     * The first item in the list will be the original title
     * @return array<array{title: string, country: string}>
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
    akas(first: 9999) {
      edges {
        node {
          country {
            text
          }
          language {
            text
          }
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
EOF;
            $data = $this->graphql->query($query, "AlsoKnow", ["id" => "tt$this->imdbID"]);

            $originalTitle = $data->title->originalTitleText->text;
            if (!empty($originalTitle)) {
                $this->akas[] = array(
                    "title" => ucwords($originalTitle),
                    "country" => "(Original Title)"
                );
            }

            foreach ($data->title->akas->edges as $edge) {
                $this->akas[] = array(
                    "title" => ucwords($edge->node->displayableProperty->value->plainText),
                    "country" => isset($edge->node->country->text) ? ucwords($edge->node->country->text) : 'Unknown'
                );
            }
        }
        usort($this->akas, fn($a, $b) => $a['country'] <=> $b['country']);
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
     * @author abe
     * @author Ed
     * @see http://projects.izzysoft.de/trac/imdbphp/ticket/117
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
    /** Get movie plots without Synopsis
     * @return array array[0..n] string plot, string author]
     * @see IMDB page /plotsummary
     */
    public function plot()
    {
        if (empty($this->plot)) {
                    $query = <<<EOF
query Plots(\$id: ID!) {
  title(id: \$id) {
    plots(first: 9999) {
      edges {
        node {
          author
          plotType
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
                if ($edge->node->plotType == 'SYNOPSIS') {
                    continue;
                }
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
    
    #-------------------------------------------[ Helper: Get IMDBID from URL ]---
    /** Get the IMDB ID from a names URL
     * @param string href url to the staff members IMDB page
     * @return string IMDBID of the staff member
     * @see used by the methods director, cast, writing, producer, composer
     */
    protected function get_imdbname($href)
    {
        return preg_replace('!^.*nm(\d+).*$!ims', '$1', $href);
    }
    
    #-----------------------------------------------------[ Helper: TableRows ]---
    /**
     * Get rows for a given table on the page
     * @param string html
     * @param string table_start
     * @return string[] Contents of each row of the table
     * @see used by the methods director, writing, producer, composer
     */
    protected function get_table_rows($id)
    {
        $xpath = $this->getXpathPage("Credits");
        if ($cells = $xpath->query("//h4[@id='$id']/following-sibling::table[1]/tbody/tr")) {
            return $cells;
        
        }
    }

    #------------------------------------------------------[ Helper: RowCells ]---
    /** Get content of table row cells
     * @param string row (as returned by imdb::get_table_rows)
     * @return array cells (array[0..n] of strings)
     * @see used by the methods director, writing, producer, composer
     */
    protected function get_row_cels($row)
    {
        if ($rowTds = $row->getElementsByTagName('td')) {
            return $rowTds;
        }
        return array();
    }

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
                    $category = "Stars";
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
     * @return array cast (array[0..n] of array[imdb,name,role,thumb])
     * e.g.
     * <pre>
     * array (
     *  'imdb' => '0922035',
     *  'name' => 'Dominic West', // Actor's name on imdb
     *  'role' => "Det. James 'Jimmy' McNulty" including all comments in brackets,
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
        $xpath = $this->getXpathPage("Credits");
        if ($castRows = $xpath->query("//table[@class='cast_list']/tr[@class=\"odd\" or @class=\"even\"]")) {
            foreach ($castRows as $castRow) {
                $castTds = $castRow->getElementsByTagName('td');
                if (4 !== count($castTds)) {
                    continue;
                }
                $dir = array(
                    'imdb' => null,
                    'name' => null,
                    'role' => null,
                    'thumb' => null
                );
                //Actor name and imdbId
                if ($actorAnchor = $castTds->item(1)->getElementsByTagName('a')->item(0)) {
                    $actorHref = $actorAnchor->getAttribute('href');
                    $dir["imdb"] = preg_replace('!.*/name/nm(\d+)/.*!ims', '$1', $actorHref);
                    $dir["name"] = trim($actorAnchor->nodeValue);
                } else {
                    if (!empty(trim($castTds->item(1)->nodeValue))) {
                       $dir["name"] = trim($castTds->item(1)->nodeValue);
                    } else {
                        continue;
                    }
                }
                // actor thumb image
                if ($imgUrl = $castTds->item(0)->getElementsByTagName('img')->item(0)->getAttribute('loadlate')) {
                    $dir["thumb"] = $imgUrl;
                } else {
                    $dir["thumb"] = '';
                }
                //Role including all comments in brackets
                if ($roleCell = $castTds->item(3)->nodeValue) {
                    $roleLines = explode("\n", $roleCell);
                    $role = '';
                    foreach ($roleLines as $key => $roleLine) {
                        //get rid of not needed episode info
                        if (strpos($roleLine, 'episode') !== false || strpos($roleLine, '/ ...') !== false || empty(trim($roleLine))) {
                            continue;
                        } else {
                            $role .=  trim(preg_replace('#[\xC2\xA0]#', '', $roleLine)) . ' ';
                        }
                    }
                }
                $dir['role'] = trim($role);
                $this->credits_cast[] = $dir;
            }
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
                    if ($keyAttributes !== array_key_last($edge->node->attributes)) {
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
        $producerRows = $this->get_table_rows("producer");
        foreach ($producerRows as $producerRow) {
            $producerTds = $this->get_row_cels($producerRow);
            $imdb = '';
            $name = '';
            $role = null;
            if (!empty(preg_replace('/[\s]+/mu', '', $producerTds->item(0)->nodeValue))) {
                if ($producerTds->item(2)) {
                    $role = trim(strip_tags($producerTds->item(2)->nodeValue));
                }
                if ($anchor = $producerTds->item(0)->getElementsByTagName('a')->item(0)) {
                    $imdb = $this->get_imdbname($anchor->getAttribute('href'));
                    $name = trim(strip_tags($anchor->nodeValue));
                } elseif (!empty($producerTds->item(0)->nodeValue)) {
                        $name = trim($producerTds->item(0)->nodeValue);
                }
                $this->credits_producer[] = array(
                    'imdb' => $imdb,
                    'name' => $name,
                    'role' => $role
                );
            }
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

    #========================================================[ /episodes page ]===
    #--------------------------------------------------------[ Season/Year check ]---
    /** Check if TV Series season or year based
     * @return string $fieldName
     */
    private function seasonYearCheck()
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
    $bySeason = count($seasonsData->title->episodes->displayableSeasons->edges);
    $byYear = count($seasonsData->title->episodes->displayableYears->edges);
    if ($byYear - $bySeason > 4) {
        $data = $seasonsData->title->episodes->displayableYears->edges;
    } else {
        $data = $seasonsData->title->episodes->displayableSeasons->edges;
    }
    return $data;

}

    #--------------------------------------------------------[ Episodes Array ]---
    /**
     * Get the series episode(s)
     * @return array episodes (array[0..n] of array[0..m] of array[imdbid,title,airdate,plot,episode,image_url])
     * array(1) {
        [1]=>
        array(13) {
            [1]=> //can be unknown, unknown_(number), seasonnumber or year
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
            string(1) "1" //can be unknown, unknown_(number) or seasonnumber
            ["image_url"]=>
            string(108) "https://m.media-amazon.com/images/M/MV5BMjM3NjI2MDA2OF5BMl5BanBnXkFtZTgwODgwNjEyMjE@._V1_UY126_UX224_AL_.jpg"
            }
        }
     * @see IMDB page /episodes
     * @version The outer and inner array keys reflects the real season and episodenumbers! Episodes can start at 0 (pilot episode)
     */
    public function episode()
    {
        if ($this->movietype() === "TV Series" || $this->movietype() === "TV Mini Series") {
            if (empty($this->season_episodes)) {
                // Check if season or year based
                $seasonsData = $this->seasonYearCheck();
                $unknownSeasonCounter = 0;
                foreach ($seasonsData as $edge) {
                    $season = $edge->node->text;
                    // To fetch data from unknown seasons/years
                    if ($edge->node->text == "Unknown") { //this is intended capitol
                        $SeasonUnknown = "unknown"; //this is intended no capitol
                        $SeasonYear = "";
                        // second or more Unknown season get number to keep them seperate.
                        if ($unknownSeasonCounter > 0) {
                            $season = $season . '_' . $unknownSeasonCounter;
                        }
                        $unknownSeasonCounter++;
                    } else {
                        $SeasonYear = $edge->node->text;
                        $SeasonUnknown = "";
                    }
//Episode Query
                    $queryEpisodes = <<<EOF
query Episodes(\$id: ID!) {
  title(id: \$id) {
    primaryImage {
      url
    }
    episodes {
      episodes(first: 9999, filter: { includeSeasons: ["$SeasonYear", "$SeasonUnknown"] }) {
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
                    $unknownEpisodeCounter = 0;
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
                                $epNumber = $epNumber . '_' . $keyEp + 1;
                            }
                        }
                        // Episode Image
                        if (isset($edge->node->primaryImage->url)) {
                            $epImageUrl = $edge->node->primaryImage->url;
                            $titleImageUrl = $episodesData->title->primaryImage->url;
                            // filter out placeholder image, in that case image = ''
                            if (stripos($edge->node->primaryImage->url, 'MV5BYjVmMjFhZmMtNjk5Ni00MTc1LWJiNjQtMTA3ZTFhNTA2NTE3XkEyXkFqcGdeQXVyMTkxNjUyNQ') == false) {
                                // Check if found episode image not equal to the title image
                                if ($epImageUrl !== $titleImageUrl) {
                                    $img = str_replace('.jpg', '', $edge->node->primaryImage->url);
                                    $imgUrl = $img . 'UY126_UX224_AL_.jpg';
                                }
                            }
                        }
                        $episode = array(
                                'imdbid' => $imdbId,
                                'title' => $title,
                                'airdate' => $airDate,
                                'plot' => $plot,
                                'episode' => $epNumber,
                                'image_url' => $imgUrl
                            );
                        $episodes[$epNumber] = $episode;
                    }
                    $this->season_episodes[$season] = $episodes;
                }
            }
        }
        return $this->season_episodes;
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
            $query = <<<EOF
query Trivia(\$id: ID!) {
  title(id: \$id) {
    trivia(first: 9999) {
      edges {
        node {
          displayableArticle {
            body {
              plaidHtml
            }
          }
          isSpoiler
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Trivia", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->trivia->edges as $edge) {
                if ($spoil === false) {
                    if (isset($edge->node->isSpoiler) && $edge->node->isSpoiler === true) {
                        continue;
                    }
                }
                $this->trivia[] = strip_tags($edge->node->displayableArticle->body->plaidHtml);
            }
        }
        
        return $this->trivia;
    }

    #======================================================[ Soundtrack ]===
    /**
     * Get the soundtrack listing
     * @return array soundtracks
     * [ soundtrack : name of the soundtrack
     *   credits : Full text only description of the credits. Contains newline characters
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
            plaidHtml
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Soundtrack", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->soundtrack->edges as $edge) {
                $credits = '';
                $title = '';
                if (isset($edge->node->text) && $edge->node->text !== '') {
                    $title = ucwords(strtolower(trim($edge->node->text)), " (");
                } else {
                    $title = 'Unknown';
                }
                foreach ($edge->node->comments as $key => $comment) {
                    if (trim(strip_tags($comment->plaidHtml)) !== '') {
                        $credits .= trim(strip_tags($comment->plaidHtml));
                        if ($key !== array_key_last($edge->node->comments)) {
                            $credits .= '&#10;';
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
     * @return array locations (array[0..n] of arrays[real,movie])
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
          displayableProperty {
            value {
              markdown
            }
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
                $real = '';
                $movie = '';
                if (isset($edge->node->displayableProperty->value->markdown)) {
                    $real = $edge->node->displayableProperty->value->markdown;
                }
                if (isset($edge->node->displayableProperty->qualifiersInMarkdownList[0]->markdown)) {
                    $movie = '(' . $edge->node->displayableProperty->qualifiersInMarkdownList[0]->markdown . ')';
                }
                $this->locations[] = array(
                    'real' => $real,
                    'movie' => $movie
                );
                
            }
        }
        return $this->locations;
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

    #-------------------------------------------------[ Main images on title page ]---
    /**
     * Get image URLs for the 6 (or how much you want) pictures on the title page
     * @return array [0..n] of string image source
     */
    public function mainphoto()
    {
        if (empty($this->main_photo)) {
            $query = <<<EOF
query MainPhoto(\$id: ID!) {
  title(id: \$id) {
    images(first: 6) {
      edges {
        node {
          url
        }
      }
    }
  }
}
EOF;

            $data = $this->graphql->query($query, "MainPhoto", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->images->edges as $edge) {
                if (isset($edge->node->url) && $edge->node->url != '') {
                    $imgUrl = str_replace('._V1_.jpg', '', $edge->node->url);
                    $this->main_photo[] = $imgUrl . '._V1_UY100_CR25,0,100,100_AL_.jpg';
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
          }
          runtime {
            value
          }
          contentType {
            displayName {
              value
            }
          }
          name {
            value
          }
        }
      }
    }
  }
}
EOF;

            $data = $this->graphql->query($query, "Video", ["id" => "tt$this->imdbID"]);
            foreach ($data->title->primaryVideos->edges as $edge) {
                if (!isset($edge->node->playbackURLs[0]->url) ||
                    !isset($edge->node->contentType->displayName->value) ||
                    $edge->node->contentType->displayName->value !== "Trailer") {
                    continue;
                }
                $rawVideoId = explode("/", parse_url($edge->node->playbackURLs[0]->url, PHP_URL_PATH));
                $embedUrl = "https://" . $this->imdbsite . "/video/imdb/" . $rawVideoId[1] . "/imdb/embed";
                $headers = get_headers($embedUrl);
                if (substr($headers[0], 9, 3) == "404" || substr($headers[0], 9, 3) == "401") {
                    continue;
                }
                $html = file_get_contents($embedUrl);
                if (stripos($html, 'class="available"') !== false) {
                    $videoUrl = $embedUrl;
                } else {
                    continue;
                }
                if (isset($edge->node->thumbnail->url) && $edge->node->thumbnail->url != '') {
                    $rawRuntime = $edge->node->runtime->value;
                    $minutes = sprintf("%02d", ($rawRuntime / 60));
                    $seconds = sprintf("%02d", $rawRuntime % 60);
                    $rawTitle = explode(":", $edge->node->name->value);
                    $titleParts = explode("|", trim($rawTitle[0]));
                    $titleParts = explode("(", trim($titleParts[0]));
                    $title = str_replace(' ', '%2520', $titleParts[0]);
                    $thumbUrl = str_replace('.jpg', '', $edge->node->thumbnail->url);
                    $thumbUrl .= '1_SP330,330,0,C,0,0,0_CR65,90,200,150_PIimdb-blackband-204-14,TopLeft,0,0_'
                                 . 'PIimdb-blackband-204-28,BottomLeft,0,1_CR0,0,200,150_'
                                 . 'PIimdb-bluebutton-big,BottomRight,-1,-1_ZATrailer,4,123,16,196,verdenab,8,255,255,255,1_'
                                 . 'ZAon%2520IMDb,4,1,14,196,verdenab,7,255,255,255,1_ZA' . $minutes . '%253A' . $seconds
                                 . ',164,1,14,36,verdenab,7,255,255,255,1_ZA' . $title
                                 . ',4,138,14,176,arialbd,7,255,255,255,1_.jpg';
                    $videoImageUrl = $thumbUrl;

                } else {
                    $videoImageUrl = '';
                }
                if (count($this->trailers) <= 2) {
                    $this->trailers[] = array(
                        'videoUrl' => $videoUrl,
                        'videoImageUrl' => $videoImageUrl
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

    protected function getPage($page = null)
    {
        if (!empty($this->page[$page])) {
            return $this->page[$page];
        }

        $this->page[$page] = parent::getPage($page);

        return $this->page[$page];
    }

    /**
     * Get all edges of a field in the title type
     * @param string $queryName The cached query name
     * @param string $fieldName The field on title you want to get
     * @param string $nodeQuery Graphql query that fits inside node { }
     * @return \stdClass[]
     */
    protected function graphQlGetAll($queryName, $fieldName, $nodeQuery)
    {
        $query = <<<EOF
query $queryName(\$id: ID!, \$after: ID) {
  title(id: \$id) {
    $fieldName(first: 9999, after: \$after) {
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
