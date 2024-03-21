<?php

#############################################################################
# IMDBPHP6                             (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# written extended & maintained by Ed                                       #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;

/**
 * A person on IMDb
 * @author Izzy (izzysoft AT qumran DOT org)
 * @author Ed
 * @copyright 2008 by Itzchak Rehberg and IzzySoft
 */
class Name extends MdbBase
{

    // "Name" page:
    protected $main_photo = null;
    protected $fullname = "";
    protected $birthday = array();
    protected $deathday = array();

    // "Bio" page:
    protected $birth_name = "";
    protected $nick_name = array();
    protected $akaName = array();
    protected $bodyheight = array();
    protected $spouses = array();
    protected $children = array();
    protected $parents = array();
    protected $relatives = array();
    protected $bio_bio = array();
    protected $bio_trivia = array();
    protected $bio_tm = array();
    protected $bio_salary = array();
    protected $bio_quotes = array();

    // "Publicity" page:
    protected $pub_prints = array();
    protected $pub_movies = array();

    // "Credits" page:
    protected $awards = array();
    protected $creditKnownFor = array();

    /**
     * @param string $id IMDBID to use for data retrieval
     * @param Config $config OPTIONAL override default config
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct($id, Config $config = null, CacheInterface $cache = null)
    {
        parent::__construct($config, $cache);
        $this->setid($id);
    }

    #=============================================================[ Main Page ]===
    #------------------------------------------------------------------[ Name ]---
    /** Get the name of the person
     * @return string name full name of the person
     * @see IMDB person page / (Main page)
     */
    public function name()
    {
        $query = <<<EOF
query Name(\$id: ID!) {
  name(id: \$id) {
    nameText {
      text
    }
  }
}
EOF;

        $data = $this->graphql->query($query, "Name", ["id" => "nm$this->imdbID"]);
        $this->fullname = isset($data->name->nameText->text) ? $data->name->nameText->text : '';
        return $this->fullname;
    }

    #--------------------------------------------------------[ Photo specific ]---

    /** Get cover photo
     * @param boolean $size (optional) small:  thumbnail (67x98, default)
     *                                 medium: image size (621x931)
     *                                 large:  image maximum size
     * @note if small or medium url 404 or 401 the full image url is returned!
     * @return mixed photo (string url if found, empty string otherwise)
     * @see IMDB person page / (Main page)
     */
    public function photo($size = "small")
    {
    $query = <<<EOF
query PrimaryImage(\$id: ID!) {
  name(id: \$id) {
    primaryImage {
      url
    }
  }
}
EOF;
        if ($this->main_photo === null) {
            $data = $this->graphql->query($query, "PrimaryImage", ["id" => "nm$this->imdbID"]);
            if ($data->name->primaryImage->url != null) {
                $img = str_replace('.jpg', '', $data->name->primaryImage->url);
                if ($size == "small") {
                    $this->main_photo = $img . 'QL100_SY98_.jpg';
                    $headers = get_headers($this->main_photo);
                    if (substr($headers[0], 9, 3) == "404" || substr($headers[0], 9, 3) == "401") {
                        $this->main_photo = $data->name->primaryImage->url;
                    }
                }
                if ($size == "medium") {
                    $this->main_photo = $img . 'QL100_SY931_.jpg';
                    $headers = get_headers($this->main_photo);
                    if (substr($headers[0], 9, 3) == "404" || substr($headers[0], 9, 3) == "401") {
                        $this->main_photo = $data->name->primaryImage->url;
                    }
                }
                if ($size == "large") {
                    $this->main_photo = $data->name->primaryImage->url;
                }
            } else {
                return $this->main_photo;
            }
        }
        return $this->main_photo;
    }

    #==================================================================[ /bio ]===
    #------------------------------------------------------------[ Birth Name ]---
    /** Get the birth name
     * @return string birthname
     * @see IMDB person page /bio
     */
    public function birthname()
    {
    $query = <<<EOF
query BirthName(\$id: ID!) {
  name(id: \$id) {
    birthName {
      text
    }
  }
}
EOF;

        $data = $this->graphql->query($query, "BirthName", ["id" => "nm$this->imdbID"]);
        $this->birth_name = isset($data->name->birthName->text) ? $data->name->birthName->text : '';
        return $this->birth_name;
    }

    #-------------------------------------------------------------[ Nick Name ]---

    /** Get the nick name
     * @return array nicknames array[0..n] of strings
     * @see IMDB person page /bio
     */
    public function nickname()
    {
        if (empty($this->nick_name)) {
            $query = <<<EOF
query NickName(\$id: ID!) {
  name(id: \$id) {
    nickNames {
      text
    }
  }
}
EOF;

            $data = $this->graphql->query($query, "NickName", ["id" => "nm$this->imdbID"]);
            foreach ($data->name->nickNames as $nickName) {
                if (!empty($nickName->text)) {
                    $this->nick_name[] = $nickName->text;
                }
            }
        }
        return $this->nick_name;
    }

    #-------------------------------------------------------------[ Alternative Names ]---
    /** Get alternative names for a person
     * @return array[0..n] of alternative names
     * @see IMDB person page /bio
     */
    public function akaName()
    {
        if (empty($this->akaName)) {
            $query = <<<EOF
query AkaName(\$id: ID!) {
  name(id: \$id) {
    akas(first: 9999) {
      edges {
        node {
          text
        }
      }
    }
  }
}
EOF;

            $data = $this->graphql->query($query, "AkaName", ["id" => "nm$this->imdbID"]);
            if ($data->name->akas->edges != null) {
                foreach ($data->name->akas->edges as $edge) {
                    $this->akaName[] = isset($edge->node->text) ? $edge->node->text : '';
                }
            } else {
                return $this->akaName;
            }
        }
        return $this->akaName;
    }

    #------------------------------------------------------------------[ Born ]---

    /** Get Birthday
     * @return array|null birthday [day,month,mon,year,place]
     *         where $monthName is the month name, and $monthInt the month number
     * @see IMDB person page /bio
     */
    public function born()
    {
        if (empty($this->birthday)) {
            $query = <<<EOF
query BirthDate(\$id: ID!) {
  name(id: \$id) {
    birthDate {
      dateComponents {
        day
        month
        year
      }
    }
    birthLocation {
      text
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "BirthDate", ["id" => "nm$this->imdbID"]);
            $day = isset($data->name->birthDate->dateComponents->day) ? $data->name->birthDate->dateComponents->day : '';
            $monthInt = isset($data->name->birthDate->dateComponents->month) ? $data->name->birthDate->dateComponents->month : '';
            $monthName = '';
            if (!empty($monthInt)) {
                $monthName = date("F", mktime(0, 0, 0, $monthInt, 10));
            }
            $year = isset($data->name->birthDate->dateComponents->year) ? $data->name->birthDate->dateComponents->year : '';
            $place = isset($data->name->birthLocation->text) ? $data->name->birthLocation->text : '';
            $this->birthday = array(
                "day" => $day,
                "month" => $monthName,
                "mon" => $monthInt,
                "year" => $year,
                "place" => $place
            );
        }
        return $this->birthday;
    }

    #------------------------------------------------------------------[ Died ]---

    /**
     * Get date of death with place and cause
     * @return array [day,monthName,monthInt,year,place,cause,status]
     *         New: Status returns current state: ALIVE,DEAD or PRESUMED_DEAD
     * @see IMDB person page /bio
     */
    public function died()
    {
        if (empty($this->deathday)) {
            $query = <<<EOF
query DeathDate(\$id: ID!) {
  name(id: \$id) {
    deathDate {
      dateComponents {
        day
        month
        year
      }
    }
    deathLocation {
      text
    }
    deathCause {
      text
    }
    deathStatus
  }
}
EOF;
            $data = $this->graphql->query($query, "DeathDate", ["id" => "nm$this->imdbID"]);
            $day = isset($data->name->deathDate->dateComponents->day) ? $data->name->deathDate->dateComponents->day : '';
            $monthInt = isset($data->name->deathDate->dateComponents->month) ? $data->name->deathDate->dateComponents->month : '';
            $monthName = '';
            if (!empty($monthInt)) {
                $monthName = date("F", mktime(0, 0, 0, $monthInt, 10));
            }
            $year = isset($data->name->deathDate->dateComponents->year) ? $data->name->deathDate->dateComponents->year : '';
            $place = isset($data->name->deathLocation->text) ? $data->name->deathLocation->text : '';
            $cause = isset($data->name->deathCause->text) ? $data->name->deathCause->text : '';
            $status = isset($data->name->deathStatus) ? $data->name->deathStatus : '';
            $this->deathday = array(
                "day" => $day,
                "month" => $monthName,
                "mon" => $monthInt,
                "year" => $year,
                "place" => $place,
                "cause" => $cause,
                "status" => $status
            );
        }
        return $this->deathday;
    }
    
    #-----------------------------------------------------------[ Body Height ]---

    /** Get the body height
     * @return array [imperial,metric] height in feet and inch (imperial) an meters (metric)
     * @see IMDB person page /bio
     */
    public function height()
    {
        if (empty($this->bodyheight)) {
            $query = <<<EOF
query BodyHeight(\$id: ID!) {
  name(id: \$id) {
    height {
      displayableProperty {
        value {
          plainText
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "BodyHeight", ["id" => "nm$this->imdbID"]);
            if (isset($data->name->height->displayableProperty->value->plainText)) {
                $heightParts = explode("(", $data->name->height->displayableProperty->value->plainText);
                $this->bodyheight["imperial"] = trim($heightParts[0]);
                if (isset($heightParts[1]) && !empty($heightParts[1])) {
                    $this->bodyheight["metric"] = trim($heightParts[1], " m)");
                } else {
                    $this->bodyheight["metric"] = '';
                }
            } else {
                return $this->bodyheight;
            }
        }
        return $this->bodyheight;
    }

    #----------------------------------------------------------------[ Spouse ]---

    /** Get spouse(s)
     * @return array [0..n] of array spouses [string imdb, string name, array from,
     *         array to, string comment, int children] where from/to are array
     *         [day,month,mon,year] (MonthName is the name, MonthInt the number of the month),
     * @see IMDB person page /bio
     */
    public function spouse()
    {
        if (empty($this->spouses)) {
            $query = <<<EOF
query Spouses(\$id: ID!) {
  name(id: \$id) {
    spouses {
      spouse {
        name {
          id
        }
        asMarkdown {
          plainText
        }
      }
      timeRange {
        fromDate {
          dateComponents {
            day
            month
            year
          }
        }
        toDate {
          dateComponents {
            day
            month
            year
          }
        }
      }
      attributes {
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Spouses", ["id" => "nm$this->imdbID"]);
            if ($data != null && $data->name->spouses != null) {
                foreach ($data->name->spouses as $spouse) {
                    // Spouse name
                    $name = isset($spouse->spouse->asMarkdown->plainText) ? $spouse->spouse->asMarkdown->plainText : '';
                    
                    // Spouse id
                    $imdbId = '';
                    if ($spouse->spouse->name != null) {
                        if (isset($spouse->spouse->name->id)) {
                            $imdbId = str_replace('nm', '', $spouse->spouse->name->id);
                        }
                    }
                    
                    // From date
                    $fromDateDay = isset($spouse->timeRange->fromDate->dateComponents->day) ? $spouse->timeRange->fromDate->dateComponents->day : '';
                    $fromDateMonthInt = isset($spouse->timeRange->fromDate->dateComponents->month) ? $spouse->timeRange->fromDate->dateComponents->month : '';
                    $fromDateMonthName = '';
                    if (!empty($fromDateMonthInt)) {
                        $fromDateMonthName = date("F", mktime(0, 0, 0, $fromDateMonthInt, 10));
                    }
                    $fromDateYear = isset($spouse->timeRange->fromDate->dateComponents->year) ? $spouse->timeRange->fromDate->dateComponents->year : '';
                    $fromDate = array(
                        "day" => $fromDateDay,
                        "month" => $fromDateMonthName,
                        "mon" => $fromDateMonthInt,
                        "year" => $fromDateYear
                    );
                    
                    // To date
                    $toDateDay = isset($spouse->timeRange->toDate->dateComponents->day) ? $spouse->timeRange->toDate->dateComponents->day : '';
                    $toDateMonthInt = isset($spouse->timeRange->toDate->dateComponents->month) ? $spouse->timeRange->toDate->dateComponents->month : '';
                    $toDateMonthName = '';
                    if (!empty($toDateMonthInt)) {
                        $toDateMonthName = date("F", mktime(0, 0, 0, $toDateMonthInt, 10));
                    }
                    $toDateYear = isset($spouse->timeRange->toDate->dateComponents->year) ? $spouse->timeRange->toDate->dateComponents->year : '';
                    $toDate = array(
                        "day" => $toDateDay,
                        "month" => $toDateMonthName,
                        "mon" => $toDateMonthInt,
                        "year" => $toDateYear
                    );
                    
                    // Comments and children
                    $comment = '';
                    $children = 0;
                    if ($spouse->attributes != null) {
                        foreach ($spouse->attributes as $key => $attribute) {
                            if (stripos($attribute->text, "child") !== false) {
                                $children = (int) preg_replace('/[^0-9]/', '', $attribute->text);
                            } else {
                                $comment .= $attribute->text;
                            }
                        }
                    }
                    $this->spouses[] = array(
                        'imdb' => $imdbId,
                        'name' => $name,
                        'from' => $fromDate,
                        'to' => $toDate,
                        'comment' => $comment,
                        'children' => $children
                    );
                }
            } else {
                return $this->spouses;
            }
        }
        return $this->spouses;
    }

    #-----------------------------------------[ Helper for children, parents, relatives ]---
    /** Parse children, parents, relatives
     * @param string $name
     *     possible values for $name: CHILDREN, PARENTS, OTHERS
     * @param array $arrayName
     * @return array
     */
    protected function nameDetailsParse($name, $arrayName)
    {
        $query = <<<EOF
query Data(\$id: ID!) {
  name(id: \$id) {
    relations(first: 9999, filter: {relationshipTypes: $name}) {
      edges {
        node {
          relationName {
            name {
              id
              nameText {
                text
              }
              
            }
            nameText
          }
          relationshipType {
            text
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Data", ["id" => "nm$this->imdbID"]);
        if ($data != null) {
            foreach ($data->name->relations->edges as $edge) {
                if (isset($edge->node->relationName->name->id)) {
                    $relName = $edge->node->relationName->name->nameText->text;
                    $relNameId = str_replace('nm', '', $edge->node->relationName->name->id);
                } else {
                    $relName = $edge->node->relationName->nameText;
                    $relNameId = '';
                }
                $relType = isset($edge->node->relationshipType->text) ? $edge->node->relationshipType->text : '';
                $arrayName[] = array(
                    'imdb' => $relNameId,
                    'name' => $relName,
                    'relType' => $relType
                );
            }
        }
        return $arrayName;
    }
    
    #----------------------------------------------------------------[ Children ]---
    /** Get the Children
     * @return array children array[0..n] of array(imdb, name, relType)
     * @see IMDB person page /bio
     */
    public function children()
    {
        if (empty($this->children)) {
            return $this->nameDetailsParse("CHILDREN", $this->children);
        }
        return $this->children;
    }
    
    #----------------------------------------------------------------[ Parents ]---
    /** Get the Parents
     * @return array parents array[0..n] of array(imdb, name, relType)
     * @see IMDB person page /bio
     */
    public function parents()
    {
        if (empty($this->parents)) {
            return $this->nameDetailsParse("PARENTS", $this->parents);
        }
        return $this->parents;
    }
    
    #----------------------------------------------------------------[ Relatives ]---
    /** Get the relatives
     * @return array relatives array[0..n] of array(imdb, name, relType)
     * @see IMDB person page /bio
     */
    public function relatives()
    {
        if (empty($this->relatives)) {
            return $this->nameDetailsParse("OTHERS", $this->relatives);
        }
        return $this->relatives;
    }

    #---------------------------------------------------------------[ MiniBio ]---

    /** Get the person's mini bio
     * @return array bio array [0..n] of array[string desc, string author]
     * @see IMDB person page /bio
     */
    public function bio()
    {
        if (empty($this->bio_bio)) {
            $query = <<<EOF
query MiniBio(\$id: ID!) {
  name(id: \$id) {
    bios(first: 9999) {
      edges {
        node {
          text {
            plainText
          }
          author {
            plainText
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "MiniBio", ["id" => "nm$this->imdbID"]);
            foreach ($data->name->bios->edges as $edge) {
                $bio_bio["desc"] = isset($edge->node->text->plainText) ? $edge->node->text->plainText : '';
                $bioAuthor = '';
                if ($edge->node->author != null) {
                    if (isset($edge->node->author->plainText)) {
                        $bioAuthor = $edge->node->author->plainText;
                    }
                }
                $bio_bio["author"] = $bioAuthor;
                $this->bio_bio[] = $bio_bio;
            }
        }
        return $this->bio_bio;
    }

    #-----------------------------------------[ Helper to Trivia, Quotes and Trademarks ]---

    /** Parse Trivia, Quotes and Trademarks
     * @param string $name
     * @param array $arrayName
     */
    protected function dataParse($name, $arrayName)
    {
        $query = <<<EOF
query Data(\$id: ID!) {
  name(id: \$id) {
    $name(first: 9999) {
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
        $data = $this->graphql->query($query, "Data", ["id" => "nm$this->imdbID"]);
        if ($data != null && $data->name->$name != null) {
            foreach ($data->name->$name->edges as $edge) {
                if (isset($edge->node->text->plainText)) {
                    $arrayName[] = $edge->node->text->plainText;
                }
            }
        }
        return $arrayName;
    }

    #----------------------------------------------------------------[ Trivia ]---

    /** Get the Trivia
     * @return array trivia array[0..n] of string
     * @see IMDB person page /bio
     */
    public function trivia()
    {
        if (empty($this->bio_trivia)) {
            return $this->dataParse("trivia", $this->bio_trivia);
        }
        return $this->bio_trivia;
    }

    #----------------------------------------------------------------[ Quotes ]---

    /** Get the Personal Quotes
     * @return array quotes array[0..n] of string
     * @see IMDB person page /bio
     */
    public function quotes()
    {
        if (empty($this->bio_quotes)) {
            return $this->dataParse("quotes", $this->bio_quotes);
        }
        return $this->bio_quotes;
    }

    #------------------------------------------------------------[ Trademarks ]---

    /** Get the "trademarks" of the person
     * @return array trademarks array[0..n] of strings
     * @see IMDB person page /bio
     */
    public function trademark()
    {
        if (empty($this->bio_tm)) {
            return $this->dataParse("trademarks", $this->bio_tm);
        }
        return $this->bio_tm;
    }

    #----------------------------------------------------------------[ Salary ]---

    /** Get the salary list
     * @return array salary array[0..n] of array [strings imdb, name, year, amount, currency, array comments[]]
     * @see IMDB person page /bio
     */
    public function salary()
    {
        if (empty($this->bio_salary)) {
            $query = <<<EOF
query Salaries(\$id: ID!) {
  name(id: \$id) {
    titleSalaries(first: 9999) {
      edges {
        node {
          title {
            titleText {
              text
            }
            id
            releaseYear {
              year
            }
          }
          amount {
            amount
            currency
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
            $data = $this->graphql->query($query, "Salaries", ["id" => "nm$this->imdbID"]);
            if ($data != null && $data->name->titleSalaries != null) {
                foreach ($data->name->titleSalaries->edges as $edge) {
                    $title = isset($edge->node->title->titleText->text) ? $edge->node->title->titleText->text : '';
                    $imdbId = isset($edge->node->title->id) ? str_replace('tt', '', $edge->node->title->id) : '';
                    $year = isset($edge->node->title->releaseYear->year) ? $edge->node->title->releaseYear->year : '';
                    $amount = isset($edge->node->amount->amount) ? $edge->node->amount->amount : '';
                    $currency = isset($edge->node->amount->currency) ? $edge->node->amount->currency : '';
                    $comments = array();
                    if ($edge->node->attributes != null) {
                        foreach ($edge->node->attributes as $attribute) {
                            if (isset($attribute->text)) {
                                $comments[] = $attribute->text;
                            }
                        }
                    }
                    $this->bio_salary[] = array(
                        'imdb' => $imdbId,
                        'name' => $title,
                        'year' => $year,
                        'amount' => $amount,
                        'currency' => $currency,
                        'comment' => $comments
                    );
                }
            } else {
                return $this->bio_salary;
            }
        }
        return $this->bio_salary;
    }

    #============================================================[ /publicity ]===
    #-----------------------------------------------------------[ Print media ]---
    /** Print media about this person
     * @return array prints array[0..n] of array[title, author, place, publisher, isbn],
     *         where "place" refers to the place of publication including year
     * @see IMDB person page /publicity
     */
    public function pubprints()
    {
        if (empty($this->pub_prints)) {
            $query = <<<EOF
query PubPrint(\$id: ID!) {
  name(id: \$id) {
    publicityListings(first: 9999, filter: {categories: ["namePrintBiography"]}) {
      edges {
        node {
          ... on NamePrintBiography {
            title {
                text
            }
            authors {
                plainText
            }
            isbn
            publisher
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "PubPrint", ["id" => "nm$this->imdbID"]);
            if ($data != null && $data->name->publicityListings != null) {
                foreach ($data->name->publicityListings->edges as $edge) {
                    $title = isset($edge->node->title->text) ? $edge->node->title->text : '';
                    $isbn = isset($edge->node->isbn) ? $edge->node->isbn : '';
                    $publisher = isset($edge->node->publisher) ? $edge->node->publisher : '';
                    $authors = array();
                    if ($edge->node->authors != null) {
                        foreach ($edge->node->authors as $author) {
                            if (isset($author->plainText)) {
                                $authors[] = $author->plainText;
                            }
                        }
                    }
                    $this->pub_prints[] = array(
                        "title" => $title,
                        "author" => $authors,
                        "publisher" => $publisher,
                        "isbn" => $isbn
                    );
                }
            } else {
                return $this->pub_prints;
            }
        }
        return $this->pub_prints;
    }

    #----------------------------------------------------[ Biographical movies ]---

    /** Biographical Movies
     * @return array pubmovies array[0..n] of array[title, id, year, seriesTitle, seriesSeason, seriesEpisode]
     * @see IMDB person page /publicity
     */
    public function pubmovies()
    {
        if (empty($this->pub_movies)) {
            $query = <<<EOF
query PubFilm(\$id: ID!) {
  name(id: \$id) {
    publicityListings(first: 9999, filter: {categories: ["nameFilmBiography"]}) {
      edges {
        node {
          ... on NameFilmBiography {
            title {
              titleText {
                text
              }
              id
              releaseYear {
                year
              }
              series {
                displayableEpisodeNumber {
                  displayableSeason {
                    text
                  }
                  episodeNumber {
                    text
                  }
                }
                series {
                  titleText {
                    text
                  }
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
            $data = $this->graphql->query($query, "PubFilm", ["id" => "nm$this->imdbID"]);
            if ($data != null && $data->name->publicityListings != null) {
                foreach ($data->name->publicityListings->edges as $edge) {
                    $filmTitle = isset($edge->node->title->titleText->text) ? $edge->node->title->titleText->text : '';
                    $filmId = isset($edge->node->title->id) ? str_replace('tt', '', $edge->node->title->id) : '';
                    $filmYear = isset($edge->node->title->releaseYear->year) ? $edge->node->title->releaseYear->year : '';
                    $filmSeriesSeason = '';
                    $filmSeriesEpisode = '';
                    $filmSeriesTitle = '';
                    if ($edge->node->title->series != null) {
                        $filmSeriesTitle = isset($edge->node->title->series->series->titleText->text) ? $edge->node->title->series->series->titleText->text : '';
                        $filmSeriesSeason = isset($edge->node->title->series->displayableEpisodeNumber->displayableSeason->text) ?
                                                  $edge->node->title->series->displayableEpisodeNumber->displayableSeason->text : '';
                        $filmSeriesEpisode = isset($edge->node->title->series->displayableEpisodeNumber->episodeNumber->text) ?
                                                   $edge->node->title->series->displayableEpisodeNumber->episodeNumber->text : '';
                    }
                    $this->pub_movies[] = array(
                        "title" => $filmTitle,
                        "id" => $filmId,
                        "year" => $filmYear,
                        "seriesTitle" => $filmSeriesTitle,
                        "seriesSeason" => $filmSeriesSeason,
                        "seriesEpisode" => $filmSeriesEpisode,
                    );
                }
            } else {
                return $this->pub_movies;
            }
        }
        return $this->pub_movies;
    }

    #-------------------------------------------------------[ Awards ]---
    /**
     * Get all awards for a name
     * @param $winsOnly Default: false, set to true to only get won awards
     * @param $event Default: "" fill eventId Example " ev0000003" to only get Oscars
     *  Possible values for $event:
     *  ev0000003 (Oscar)
     *  ev0000223 (Emmy)
     *  ev0000292 (Golden Globe)
     * @return array[festivalName][0..n] of 
     *      array[awardYear,awardWinner(bool),awardCategory,awardName,awardNotes
     *      array awardTitles[titleId,titleName,titleNote],awardOutcome] array total(win, nom)
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
     *                   [awardTitles] => Array
     *                       (
     *                           [0] => Array
     *                               (
     *                                   [titleId] => 0000040
     *                                   [titleName] => 1408
     *                                   [titleNote] => screenplay/director
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
        if (empty($this->awards)) {
            $query = <<<EOF
query Award(\$id: ID!) {
  name(id: \$id) {
    awardNominations(
      first: 9999
      sort: {by: PRESTIGIOUS, order: DESC}
      filter: {wins: $winsOnly $event}
    ) {
      edges {
        node {
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
            ... on AwardedNames {
              secondaryAwardTitles {
                title {
                  id
                  titleText {
                    text
                  }
                }
                note {
                  plainText
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
            $data = $this->graphql->query($query, "Award", ["id" => "nm$this->imdbID"]);
            $winnerCount = 0;
            $nomineeCount = 0;
            foreach ($data->name->awardNominations->edges as $edge) {
                $eventName = isset($edge->node->award->event->text) ? $edge->node->award->event->text : '';
                $eventEditionYear = isset($edge->node->award->eventEdition->year) ? $edge->node->award->eventEdition->year : '';
                $awardName = isset($edge->node->award->text) ? $edge->node->award->text : '';
                $awardCategory = isset($edge->node->award->category->text) ? $edge->node->award->category->text : '';
                $awardNotes = isset($edge->node->award->notes->plainText) ? $edge->node->award->notes->plainText : '';
                $awardIsWinner = $edge->node->isWinner;
                $conclusion = $awardIsWinner === true ? "Winner" : "Nominee";
                $awardIsWinner === true ? $winnerCount++ : $nomineeCount++;
                
                //credited titles
                $titles = array();
                if ($edge->node->awardedEntities->secondaryAwardTitles !== null) {
                    foreach ($edge->node->awardedEntities->secondaryAwardTitles as $title) {
                        $titleName = isset($title->title->titleText->text) ? $title->title->titleText->text : '';
                        $titleId = isset($title->title->id) ? $title->title->id : '';
                        $titleNote = isset($title->note->plainText) ? $title->note->plainText : '';
                        $titles[] = array(
                            'titleId' => str_replace('tt', '', $titleId),
                            'titleName' => $titleName,
                            'titleNote' => trim($titleNote, " ()")
                        );
                    }
                }
                
                $this->awards[$eventName][] = array(
                    'awardYear' => $eventEditionYear,
                    'awardWinner' => $awardIsWinner,
                    'awardCategory' => $awardCategory,
                    'awardName' => $awardName,
                    'awardNotes' => $awardNotes,
                    'awardTitles' => $titles,
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

    #============================================================[ /creditKnownFor ]===
    /** All prestigious title credits for this person
     * @return array creditKnownFor array[0..n] of array[title, titleId, titleYear, titleEndYear, array titleCharacters]
     * @see IMDB person page /credits
     */
    public function creditKnownFor()
    {
        if (empty($this->creditKnownFor)) {
            $query = <<<EOF
query KnownFor(\$id: ID!) {
  name(id: \$id) {
    knownFor(first: 9999) {
      edges {
        node{
          credit {
            title {
              id
              titleText {
                text
              }
              releaseYear {
                year
                endYear
              }
            }
            ... on Cast {
              characters {
                name
              }
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "KnownFor", ["id" => "nm$this->imdbID"]);
            if ($data != null) {
                foreach ($data->name->knownFor->edges as $edge) {
                    $title = isset($edge->node->credit->title->titleText->text) ?
                                   $edge->node->credit->title->titleText->text : '';
                                   
                    $titleId = isset($edge->node->credit->title->id) ?
                                     str_replace('tt', '', $edge->node->credit->title->id) : '';
                                     
                    $titleYear = isset($edge->node->credit->title->releaseYear->year) ?
                                       $edge->node->credit->title->releaseYear->year : null;
                                       
                    $titleEndYear = isset($edge->node->credit->title->releaseYear->endYear) ?
                                          $edge->node->credit->title->releaseYear->endYear : null;
                                        
                    $characters = array();
                    if ($edge->node->credit->characters != null) {
                        foreach ($edge->node->credit->characters as $character) {
                            $characters[] = $character->name;
                        }
                    }
                    $this->creditKnownFor[] = array(
                        'title' => $title,
                        'titleId' => $titleId,
                        'titleYear' => $titleYear,
                        'titleEndYear' => $titleEndYear,
                        'titleCharacters' => $characters
                    );
                }
            } else {
                return $this->creditKnownFor;
            }
        }
        return $this->creditKnownFor;
    }
}
