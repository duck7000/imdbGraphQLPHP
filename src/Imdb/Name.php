<?php

#############################################################################
# imdbGraphQLPHP                       (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# written extended & maintained by Ed                                       #
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
 * A person on IMDb
 * @author Izzy (izzysoft AT qumran DOT org)
 * @author Ed
 * @copyright 2008 by Itzchak Rehberg and IzzySoft
 */
class Name extends MdbBase
{

    // "Name" page:
    protected $imageFunctions;
    protected $mainPoster = null;
    protected $mainPosterThumb = null;
    protected $fullName = null;
    protected $birthday = array();
    protected $deathday = array();
    protected $age = null;
    protected $professions = array();
    protected $popRank = array();
    protected $mainPhoto = array();
    protected $videos = array();
    protected $news = array();

    // "Bio" page:
    protected $birthName = null;
    protected $nickName = array();
    protected $akaName = array();
    protected $bodyheight = array();
    protected $spouses = array();
    protected $children = array();
    protected $parents = array();
    protected $relatives = array();
    protected $bioBio = array();
    protected $bioTrivia = array();
    protected $bioQuotes = array();
    protected $bioTrademark = array();
    protected $bioSalary = array();

    // "Publicity" page:
    protected $pubPrints = array();
    protected $pubMovies = array();
    protected $pubPortrayal = array();
    protected $pubArticle = array();
    protected $pubInterview = array();
    protected $pubMagazine = array();
    protected $pubPictorial = array();

    // "OtherWorks" page:
    protected $otherWorks = array();

    // "External Sites" page:
    protected $externalSites = array();

    // "Credits" page:
    protected $awards = array();
    protected $creditKnownFor = array();
    protected $credits = array();

    #----------------------------------------------------------[ Helper for NameSearch class ]---
    /**
     * Create an person object populated with id and name
     * @param string $id name ID
     * @param string $name person name
     * @param Config $config
     * @param LoggerInterface $logger OPTIONAL override default logger
     * @param CacheInterface $cache OPTIONAL override default cache
     * @return Name
     */
    public static function fromSearchResult(
        $id,
        $name,
        Config $config = null,
        LoggerInterface $logger = null,
        CacheInterface $cache = null
    ) {
        $person = new Name($id, $config, $logger, $cache);
        $person->fullName = $name;
        return $person;
    }

    /**
     * @param string $id IMDBID to use for data retrieval
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

    #=============================================================[ Main Page ]===

    #------------------------------------------------------------------[ Name ]---
    /** Get the name of the person
     * @return string name full name of the person
     * @see IMDB person page / (Main page)
     */
    public function name()
    {
        if (empty($this->fullName)) {
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
            if (!empty($data->name->nameText->text)) {
                $this->fullName = $data->name->nameText->text;
            }
        }
        return $this->fullName;
    }

    #--------------------------------------------------------[ Photo specific ]---
    /**
     * Get the main photo image url for thumbnail or full size
     * @param boolean $thumb get the thumbnail (140x207 pixels) or large (max 1000 pixels)
     * @return string|false photo (string URL if found, FALSE otherwise)
     * @see IMDB page / (NamePage)
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
     * @see IMDB page / (NamePage)
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

    /**
     * Get the URL for the Name cover image
     * @param boolean $thumb get the thumbnail (default) or the
     *        bigger variant (max width 1000 pixels - FALSE)
     * @return mixed url (string URL or FALSE if none)
     * @see IMDB page / (NamePage)
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
        $path = $this->config->photoroot . "nm{$this->imdbid()}" . "{$ext}.jpg";
        if (file_exists($path)) {
            return $this->config->photodir . "nm{$this->imdbid()}" . "{$ext}.jpg";
        }
        if (!is_writable($this->config->photoroot)) {
            $this->debug_scalar("<BR>***ERROR*** The configured image directory lacks write permission!<BR>");
            return false;
        }
        if ($this->savephoto($path, $thumb)) {
            return $this->config->photodir . "nm{$this->imdbid()}" . "{$ext}.jpg";
        }
        return false;
    }
    
    #==================================================================[ /bio ]===
    #------------------------------------------------------------[ Birth Name ]---
    /** Get the birth name
     * @return string birthname
     * @see IMDB person page /bio
     */
    public function birthname()
    {
        if (empty($this->birthName)) {
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
            if (!empty($data->name->birthName->text)) {
                $this->birthName = $data->name->birthName->text;
            }
        }
        return $this->birthName;
    }

    #-------------------------------------------------------------[ Nick Name ]---
    /** Get the nick name
     * @return array nicknames array[0..n] of strings
     * @see IMDB person page /bio
     */
    public function nickname()
    {
        if (empty($this->nickName)) {
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
                    $this->nickName[] = $nickName->text;
                }
            }
        }
        return $this->nickName;
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
            foreach ($data->name->akas->edges as $edge) {
                if (!empty($edge->node->text)) {
                    $this->akaName[] = $edge->node->text;
                }
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
            $monthInt = isset($data->name->birthDate->dateComponents->month) ?
                              $data->name->birthDate->dateComponents->month : null;
            $monthName = null;
            if (!empty($monthInt)) {
                $monthName = date("F", mktime(0, 0, 0, $monthInt, 10));
            }
            $this->birthday = array(
                "day" => isset($data->name->birthDate->dateComponents->day) ?
                               $data->name->birthDate->dateComponents->day : null,
                "month" => $monthName,
                "mon" => $monthInt,
                "year" => isset($data->name->birthDate->dateComponents->year) ?
                                $data->name->birthDate->dateComponents->year : null,
                "place" => isset($data->name->birthLocation->text) ?
                                 $data->name->birthLocation->text : null
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
            $monthInt = isset($data->name->deathDate->dateComponents->month) ?
                              $data->name->deathDate->dateComponents->month : null;
            $monthName = null;
            if (!empty($monthInt)) {
                $monthName = date("F", mktime(0, 0, 0, $monthInt, 10));
            }
            $this->deathday = array(
                "day" => isset($data->name->deathDate->dateComponents->day) ?
                               $data->name->deathDate->dateComponents->day : null,
                "month" => $monthName,
                "mon" => $monthInt,
                "year" => isset($data->name->deathDate->dateComponents->year) ?
                                $data->name->deathDate->dateComponents->year : null,
                "place" => isset($data->name->deathLocation->text) ?
                                 $data->name->deathLocation->text : null,
                "cause" => isset($data->name->deathCause->text) ?
                                 $data->name->deathCause->text : null,
                "status" => isset($data->name->deathStatus) ?
                                  $data->name->deathStatus : null
            );
        }
        return $this->deathday;
    }

    #------------------------------------------------------------------[ Age ]---
    /** Get the age of the person
     * @return int age
     * @see IMDB person page / (Main page)
     */
    public function age()
    {
        if (empty($this->age)) {
            $query = <<<EOF
query Age(\$id: ID!) {
  name(id: \$id) {
    age {
      value
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Age", ["id" => "nm$this->imdbID"]);
            if (!empty($data->name->age->value)) {
                $this->age = $data->name->age->value;
            }
        }
        return $this->age;
    }

    #-----------------------------------------------------------[ Primary Professions ]---
    /** Get primary professions of this person
     * @return array() all professions
     * @see IMDB person page
     */
    public function profession()
    {
        if (empty($this->professions)) {
            $query = <<<EOF
query Professions(\$id: ID!) {
  name(id: \$id) {
    primaryProfessions {
      category {
        text
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Professions", ["id" => "nm$this->imdbID"]);
            foreach ($data->name->primaryProfessions as $primaryProfession) {
                if (!empty($primaryProfession->category->text)) {
                    $this->professions[] = $primaryProfession->category->text;
                }
            }
        }
        return $this->professions;
    }

    #----------------------------------------------------------[ Popularity ]---
    /**
     * Get current popularity rank of a person
     * @return array(currentRank: int, changeDirection: string, difference: int)
     * @see IMDB page / (NamePage)
     */
    public function rank()
    {
        if (empty($this->popRank)) {
            $query = <<<EOF
query Rank(\$id: ID!) {
  name(id: \$id) {
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

            $data = $this->graphql->query($query, "Rank", ["id" => "nm$this->imdbID"]);
            if (!empty($data->name->meterRanking->currentRank)) {
                $this->popRank = array(
                    'currentRank' => $data->name->meterRanking->currentRank,
                    'changeDirection' => isset($data->name->meterRanking->rankChange->changeDirection) ?
                                               $data->name->meterRanking->rankChange->changeDirection : null,
                    'difference' => isset($data->name->meterRanking->rankChange->difference) ?
                                          $data->name->meterRanking->rankChange->difference : null
                );
            }
        }
        return $this->popRank;
    }

    #-----------------------------------------------------------[ Body Height ]---
    /** Get the body height
     * @return array [imperial: array[feet (int), inches (float)], metric: int (in centimeters)]
     * @see IMDB person page /bio
     */
    public function height()
    {
        if (empty($this->bodyheight)) {
            $query = <<<EOF
query BodyHeight(\$id: ID!) {
  name(id: \$id) {
    height {
      measurement {
        value
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "BodyHeight", ["id" => "nm$this->imdbID"]);
            if (!empty($data->name->height->measurement->value)) {
                $value = $data->name->height->measurement->value;
                $inchesTotal = $value * 0.393701;
                $feet = intval($inchesTotal / 12);
                $inches = $inchesTotal - ($feet * 12);
                $imperial = array(
                    'feet' => $feet,
                    'inches' => $inches
                );
                $this->bodyheight = array(
                    'imperial' => $imperial,
                    'metric' => $value
                );
            }
        }
        return $this->bodyheight;
    }

    #----------------------------------------------------------------[ Spouse ]---
    /** Get spouse(s)
     * @return array [0..n] of array spouses [imdb, name, array from,
     *         array to, dateText, comment array(), children] where from/to are array
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
        displayableProperty {
          value {
            plainText
          }
        }
      }
      attributes {
        text
      }
      current
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "Spouses", ["id" => "nm$this->imdbID"]);
            if (!empty($data->name->spouses)) {
                foreach ($data->name->spouses as $spouse) {
                    // Spouse id
                    $imdbId = null;
                    if (!empty($spouse->spouse->name)) {
                        if (!empty($spouse->spouse->name->id)) {
                            $imdbId = str_replace('nm', '', $spouse->spouse->name->id);
                        }
                    }
                    // From date
                    $fromDateMonthInt = isset($spouse->timeRange->fromDate->dateComponents->month) ?
                                              $spouse->timeRange->fromDate->dateComponents->month : null;
                    $fromDateMonthName = null;
                    if (!empty($fromDateMonthInt)) {
                        $fromDateMonthName = date("F", mktime(0, 0, 0, $fromDateMonthInt, 10));
                    }
                    $fromDate = array(
                        "day" => isset($spouse->timeRange->fromDate->dateComponents->day) ?
                                       $spouse->timeRange->fromDate->dateComponents->day : null,
                        "month" => $fromDateMonthName,
                        "mon" => $fromDateMonthInt,
                        "year" => isset($spouse->timeRange->fromDate->dateComponents->year) ?
                                        $spouse->timeRange->fromDate->dateComponents->year : null
                    );
                    // To date
                    $toDateMonthInt = isset($spouse->timeRange->toDate->dateComponents->month) ?
                                            $spouse->timeRange->toDate->dateComponents->month : null;
                    $toDateMonthName = null;
                    if (!empty($toDateMonthInt)) {
                        $toDateMonthName = date("F", mktime(0, 0, 0, $toDateMonthInt, 10));
                    }
                    $toDate = array(
                        "day" => isset($spouse->timeRange->toDate->dateComponents->day) ?
                                       $spouse->timeRange->toDate->dateComponents->day : null,
                        "month" => $toDateMonthName,
                        "mon" => $toDateMonthInt,
                        "year" => isset($spouse->timeRange->toDate->dateComponents->year) ?
                                        $spouse->timeRange->toDate->dateComponents->year : null
                    );
                    // Comments and children
                    $comment = array();
                    $children = 0;
                    if (!empty($spouse->attributes)) {
                        foreach ($spouse->attributes as $key => $attribute) {
                            if (!empty($attribute->text)) {
                                if (stripos($attribute->text, "child") !== false) {
                                    $children = (int) preg_replace('/[^0-9]/', '', $attribute->text);
                                } else {
                                    $comment[] = $attribute->text;
                                }
                            }
                        }
                    }
                    $this->spouses[] = array(
                        'imdb' => $imdbId,
                        'name' => isset($spouse->spouse->asMarkdown->plainText) ?
                                        $spouse->spouse->asMarkdown->plainText : null,
                        'from' => $fromDate,
                        'to' => $toDate,
                        'dateText' => isset($spouse->timeRange->displayableProperty->value->plainText) ?
                                            $spouse->timeRange->displayableProperty->value->plainText : null,
                        'comment' => $comment,
                        'children' => $children,
                        'current' => $spouse->current
                    );
                }
            }
        }
        return $this->spouses;
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
        if (empty($this->bioBio)) {
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
                $this->bioBio[] = array(
                    'desc' => isset($edge->node->text->plainText) ?
                                    $edge->node->text->plainText : null,
                    'author' => isset($edge->node->author->plainText) ?
                                      $edge->node->author->plainText : null
                );
            }
        }
        return $this->bioBio;
    }

    #----------------------------------------------------------------[ Trivia ]---
    /** Get the Trivia
     * @return array trivia array[0..n] of string
     * @see IMDB person page /bio
     */
    public function trivia()
    {
        if (empty($this->bioTrivia)) {
            return $this->dataParse("trivia", $this->bioTrivia);
        }
        return $this->bioTrivia;
    }

    #----------------------------------------------------------------[ Quotes ]---
    /** Get the Personal Quotes
     * @return array quotes array[0..n] of string
     * @see IMDB person page /bio
     */
    public function quotes()
    {
        if (empty($this->bioQuotes)) {
            return $this->dataParse("quotes", $this->bioQuotes);
        }
        return $this->bioQuotes;
    }

    #------------------------------------------------------------[ Trademarks ]---
    /** Get the "trademarks" of the person
     * @return array trademarks array[0..n] of strings
     * @see IMDB person page /bio
     */
    public function trademark()
    {
        if (empty($this->bioTrademark)) {
            return $this->dataParse("trademarks", $this->bioTrademark);
        }
        return $this->bioTrademark;
    }

    #----------------------------------------------------------------[ Salary ]---
    /** Get the salary list
     * @return array salary array[0..n] of array [strings imdb, name, year, amount, currency, array comments[]]
     * @see IMDB person page /bio
     */
    public function salary()
    {
        if (empty($this->bioSalary)) {
            $query = <<<EOF
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
EOF;
            $data = $this->graphQlGetAll("Salaries", "titleSalaries", $query);
            foreach ($data as $edge) {
                $comments = array();
                if (!empty($edge->node->attributes)) {
                    foreach ($edge->node->attributes as $attribute) {
                        if (!empty($attribute->text)) {
                            $comments[] = $attribute->text;
                        }
                    }
                }
                $this->bioSalary[] = array(
                    'imdb' => isset($edge->node->title->id) ?
                                    str_replace('tt', '', $edge->node->title->id) : null,
                    'name' => isset($edge->node->title->titleText->text) ?
                                    $edge->node->title->titleText->text : null,
                    'year' => isset($edge->node->title->releaseYear->year) ?
                                    $edge->node->title->releaseYear->year : null,
                    'amount' => isset($edge->node->amount->amount) ?
                                      $edge->node->amount->amount : null,
                    'currency' => isset($edge->node->amount->currency) ?
                                        $edge->node->amount->currency : null,
                    'comment' => $comments
                );
            }
        }
        return $this->bioSalary;
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
        if (empty($this->pubPrints)) {
            $filter = ', filter: {categories: ["namePrintBiography"]}';
            $query = <<<EOF
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
EOF;
            $data = $this->graphQlGetAll("PubPrint", "publicityListings", $query, $filter);
            foreach ($data as $edge) {
                $authors = array();
                if (!empty($edge->node->authors)) {
                    foreach ($edge->node->authors as $author) {
                        if (!empty($author->plainText)) {
                            $authors[] = $author->plainText;
                        }
                    }
                }
                $this->pubPrints[] = array(
                    "title" => isset($edge->node->title->text) ?
                                     $edge->node->title->text : null,
                    "author" => $authors,
                    "publisher" => isset($edge->node->publisher) ?
                                         $edge->node->publisher : null,
                    "isbn" => isset($edge->node->isbn) ?
                                    $edge->node->isbn : null
                );
            }
        }
        return $this->pubPrints;
    }

    #----------------------------------------------------[ Biographical movies ]---
    /** Biographical Movies
     * @return array pubmovies array[0..n] of array[title, id, year, seriesTitle, seriesSeason, seriesEpisode]
     * @see IMDB person page /publicity
     */
    public function pubmovies()
    {
        if (empty($this->pubMovies)) {
            $filter = ', filter: {categories: ["nameFilmBiography"]}';
            $query = <<<EOF
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
EOF;
            $data = $this->graphQlGetAll("PubFilm", "publicityListings", $query, $filter);
            foreach ($data as $edge) {
                $this->pubMovies[] = array(
                    "title" => isset($edge->node->title->titleText->text) ?
                                     $edge->node->title->titleText->text : null,
                    "id" => isset($edge->node->title->id) ?
                                  str_replace('tt', '', $edge->node->title->id) : null,
                    "year" => isset($edge->node->title->releaseYear->year) ?
                                    $edge->node->title->releaseYear->year : null,
                    "seriesTitle" => isset($edge->node->title->series->series->titleText->text) ?
                                           $edge->node->title->series->series->titleText->text : null,
                    "seriesSeason" => isset($edge->node->title->series->displayableEpisodeNumber->displayableSeason->text) ?
                                            $edge->node->title->series->displayableEpisodeNumber->displayableSeason->text : null,
                    "seriesEpisode" => isset($edge->node->title->series->displayableEpisodeNumber->episodeNumber->text) ?
                                             $edge->node->title->series->displayableEpisodeNumber->episodeNumber->text : null,
                );
            }
        }
        return $this->pubMovies;
    }

    #-----------------------------------------------------------[ Portrayal]---
    /** Portrayal listings about this person
     * @return array portrayal array[0..n] of array[title, id, year]
     * @see IMDB person page /publicity
     */
    public function pubportrayal()
    {
        if (empty($this->pubPortrayal)) {
            $filter = ', filter: {categories: ["namePortrayal"]}';
            $query = <<<EOF
... on NamePortrayal {
  title {
    titleText {
      text
    }
    id
    releaseYear {
      year
    }
  }
}
EOF;
            $data = $this->graphQlGetAll("PubPortrayal", "publicityListings", $query, $filter);
            foreach ($data as $edge) {
                $this->pubPortrayal[] = array(
                    'title' => isset($edge->node->title->titleText->text) ?
                                     $edge->node->title->titleText->text : null,
                    'id' => isset($edge->node->title->id) ?
                                  str_replace('tt', '', $edge->node->title->id) : null,
                    'year' => isset($edge->node->title->releaseYear->year) ?
                                    $edge->node->title->releaseYear->year : null
                );
            }
        }
        return $this->pubPortrayal;
    }

    #----------------------------------------------------------------[ Article ]---
    /** Get the Publicity Articles of this name
     * @return array()
     *      [publication] =>    (string)
     *      [regionId] =>       (string)
     *      [title] =>          (string)
     *      [date] => Array()
     *          [day] =>    (int)
     *          [month] =>  (int)
     *          [year] =>   (int)
     *      [reference] =>      (string)
     *      [authors] => Array()
     *          [0] => (string)
     * @see IMDB person page /publicity
     */
    public function pubarticle()
    {
        if (empty($this->pubArticle)) {
            $this->pubArticle = $this->pubOtherListing("PublicityArticle");
        }
        return $this->pubArticle;
    }

    #----------------------------------------------------------------[ Interview ]---
    /** Get the Publicity Interviews of this name
     * @return array()
     *      [publication] =>    (string)
     *      [regionId] =>       (string)
     *      [title] =>          (string)
     *      [date] => Array()
     *          [day] =>    (int)
     *          [month] =>  (int)
     *          [year] =>   (int)
     *      [reference] =>      (string)
     *      [authors] => Array()
     *          [0] => (string)
     * @see IMDB person page /publicity
     */
    public function pubinterview()
    {
        if (empty($this->pubInterview)) {
            $this->pubInterview = $this->pubOtherListing("PublicityInterview");
        }
        return $this->pubInterview;
    }

    #----------------------------------------------------------------[ Magazines ]---
    /** Get the Publicity Magazines of this name
     * @return array()
     *      [publication] =>    (string)
     *      [regionId] =>       (string)
     *      [title] =>          (string)
     *      [date] => Array()
     *          [day] =>    (int)
     *          [month] =>  (int)
     *          [year] =>   (int)
     *      [reference] =>      (string)
     *      [authors] => Array()
     *          [0] => (string)
     * @see IMDB person page /publicity
     */
    public function pubmagazine()
    {
        if (empty($this->pubMagazine)) {
            $this->pubMagazine = $this->pubOtherListing("PublicityMagazineCover");
        }
        return $this->pubMagazine;
    }

    #----------------------------------------------------------------[ Pictorial ]---
    /** Get the Publicity Pictoryials of this name
     * @return array()
     *      [publication] =>    (string)
     *      [regionId] =>       (string)
     *      [title] =>          (string)
     *      [date] => Array()
     *          [day] =>    (int)
     *          [month] =>  (int)
     *          [year] =>   (int)
     *      [reference] =>      (string)
     *      [authors] => Array()
     *          [0] => (string)
     * @see IMDB person page /publicity
     */
    public function pubpictorial()
    {
        if (empty($this->pubPictorial)) {
            $this->pubPictorial = $this->pubOtherListing("PublicityPictorial");
        }
        return $this->pubPictorial;
    }

    #============================================================[ /OtherWorks ]===

    /** Other works of this person
     * @return array pubOtherWorks array[0..n] of array[category, fromDate array(day, month,year), toDate array(day, month,year), text]
     * @see IMDB person page /otherworks
     */
    public function otherWorks()
    {
        if (empty($this->otherWorks)) {
            $query = <<<EOF
category {
  text
}
fromDate
toDate
text {
  plainText
}
EOF;
            $data = $this->graphQlGetAll("OtherWorks", "otherWorks", $query);
            foreach ($data as $edge) {
                // From date
                $fromDate = array(
                    "day" => isset($edge->node->fromDate->day) ?
                                   $edge->node->fromDate->day : null,
                    "month" => isset($edge->node->fromDate->month) ?
                                     $edge->node->fromDate->month : null,
                    "year" => isset($edge->node->fromDate->year) ?
                                    $edge->node->fromDate->year : null
                );
                // To date
                $toDate = array(
                    "day" => isset($edge->node->toDate->day) ?
                                   $edge->node->toDate->day : null,
                    "month" => isset($edge->node->toDate->month) ?
                                     $edge->node->toDate->month : null,
                    "year" => isset($edge->node->toDate->year) ?
                                    $edge->node->toDate->year : null
                );
                $this->otherWorks[] = array(
                    "category" => isset($edge->node->category) ?
                                        $edge->node->category->text : null,
                    "fromDate" => $fromDate,
                    "toDate" => $toDate,
                    "text" => isset($edge->node->text->plainText) ?
                                    $edge->node->text->plainText : null
                );
            }
        }
        return $this->otherWorks;
    }

    #-------------------------------------------------------[ External sites ]---
    /** external websites with info of this name, excluding external reviews.
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
                $language = array();
                if (!empty($edge->node->externalLinkLanguages)) {
                    foreach ($edge->node->externalLinkLanguages as $lang) {
                        if (!empty($lang->text)) {
                            $language[] = $lang->text;
                        }
                    }
                }
                $this->externalSites[$categoryIds[$edge->node->externalLinkCategory->id]][] = array(
                    'label' => !empty($edge->node->label) ?
                                      $edge->node->label : null,
                    'url' => !empty($edge->node->url) ?
                                    $edge->node->url : null,
                    'language' => $language
                );
            }
        }
        return $this->externalSites;
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
  name(id: \$id) {
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
            $data = $this->graphql->query($query, "MainPhoto", ["id" => "nm$this->imdbID"]);
            foreach ($data->name->images->edges as $edge) {
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

    #-------------------------------------------------------[ Awards ]---
    /**
     * Get all awards for a name
     * @param $winsOnly boolean Default: false, set to true to only get won awards
     * @param $event string Default: "" eventId Example " ev0000003" to only get Oscars
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
     *                                   [titleFullImageUrl] => https://m.media-amazon.com/images/M/MV5BMTg3ODY2ODM3OF5BMl5BanBnXkFtZTYwOTQ5NTM3._V1_.jpg
     *                                   [titleThumbImageUrl] => https://m.media-amazon.com/images/M/MV5BMTg3ODY2ODM3OF5BMl5BanBnXkFtZTYwOTQ5NTM3._V1_QL75_SX281_.jpg
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
        if (empty($this->awards)) {
            $filter = $this->awardFilter($winsOnly, $event);
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
  ... on AwardedNames {
    secondaryAwardTitles {
      title {
        id
        titleText {
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
                //credited titles
                $titles = array();
                if (!empty($edge->node->awardedEntities->secondaryAwardTitles)) {
                    foreach ($edge->node->awardedEntities->secondaryAwardTitles as $title) {
                        $titleThumbImageUrl = null;
                        $titleFullImageUrl = null;
                        if (!empty($title->title->primaryImage->url)) {
                            $img = str_replace('.jpg', '', $title->title->primaryImage->url);
                            $titleFullImageUrl = $img . 'QL100_UX1000_.jpg';
                            $fullImageWidth = $title->title->primaryImage->width;
                            $fullImageHeight = $title->title->primaryImage->height;
                            $newImageWidth = 140;
                            $newImageHeight = 207;
                            $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                            $titleThumbImageUrl = $img . $parameter;
                        }
                        $titles[] = array(
                            'titleId' => isset($title->title->id) ?
                                               str_replace('tt', '', $title->title->id) : null,
                            'titleName' => isset($title->title->titleText->text) ?
                                                 $title->title->titleText->text : null,
                            'titleNote' => isset($title->note->plainText) ?
                                                 trim($title->note->plainText, " ()") : null,
                            'titleFullImageUrl' => $titleFullImageUrl,
                            'titleThumbImageUrl' => $titleThumbImageUrl
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
     * @return array creditKnownFor array[0..n] of array[title, titleId, titleYear, titleEndYear, titleFullImageUrl, titleThumbImageUrl, array titleCharacters]
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
            }
          }
        }
      }
    }
  }
}
EOF;
            $data = $this->graphql->query($query, "KnownFor", ["id" => "nm$this->imdbID"]);
            foreach ($data->name->knownFor->edges as $edge) {
                $titleThumbImageUrl = null;
                $titleFullImageUrl = null;
                if (!empty($edge->node->credit->title->primaryImage->url)) {
                    $img = str_replace('.jpg', '', $edge->node->credit->title->primaryImage->url);
                    $titleFullImageUrl = $img . 'QL100_UX1000_.jpg';
                    $fullImageWidth = $edge->node->credit->title->primaryImage->width;
                    $fullImageHeight = $edge->node->credit->title->primaryImage->height;
                    $newImageWidth = 140;
                    $newImageHeight = 207;
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                    $titleThumbImageUrl = $img . $parameter;
                }
                $characters = array();
                if (!empty($edge->node->credit->characters)) {
                    foreach ($edge->node->credit->characters as $character) {
                        if (!empty($character->name)) {
                            $characters[] = $character->name;
                        }
                    }
                }
                $this->creditKnownFor[] = array(
                    'title' => isset($edge->node->credit->title->titleText->text) ?
                                     $edge->node->credit->title->titleText->text : null,
                    'titleId' => isset($edge->node->credit->title->id) ?
                                       str_replace('tt', '', $edge->node->credit->title->id) : null,
                    'titleYear' => isset($edge->node->credit->title->releaseYear->year) ?
                                         $edge->node->credit->title->releaseYear->year : null,
                    'titleEndYear' => isset($edge->node->credit->title->releaseYear->endYear) ?
                                            $edge->node->credit->title->releaseYear->endYear : null,
                    'titleCharacters' => $characters,
                    'titleFullImageUrl' => $titleFullImageUrl,
                    'titleThumbImageUrl' => $titleThumbImageUrl
                );
            }
        }
        return $this->creditKnownFor;
    }

    #-------------------------------------------------------[ Credits ]---
    /** Get all credits for a person
     * @return array[categoryId] of array('titleId: string, 'titleName: string, titleType: string,
     *      year: int, endYear: int, characters: array(),jobs: array(), titleFullImageUrl, titleThumbImageUrl,)
     * @see IMDB page /credits
     */
    public function credit()
    {
        // imdb credits category ids to camelCase names
        $categoryIds = array(
            'director' => 'director',
            'writer' => 'writer',
            'actress' => 'actress',
            'actor' => 'actor',
            'producer' => 'producer',
            'composer' => 'composer',
            'cinematographer' => 'cinematographer',
            'editor' => 'editor',
            'casting_director' => 'castingDirector',
            'production_designer' => 'productionDesigner',
            'art_director' => 'artDirector',
            'set_decorator' => 'setDecorator',
            'costume_designer' => 'costumeDesigner',
            'make_up_department' => 'makeUpDepartment',
            'production_manager' => 'productionManager',
            'assistant_director' => 'assistantDirector',
            'art_department' => 'artDepartment',
            'sound_department' => 'soundDepartment',
            'special_effects' => 'specialEffects',
            'visual_effects' => 'visualEffects',
            'stunts' => 'stunts',
            'choreographer' => 'choreographer',
            'camera_department' => 'cameraDepartment',
            'animation_department' => 'animationDepartment',
            'casting_department' => 'castingDepartment',
            'costume_department' => 'costumeDepartment',
            'editorial_department' => 'editorialDepartment',
            'electrical_department' => 'electricalDepartment',
            'location_management' => 'locationManagement',
            'music_department' => 'musicDepartment',
            'production_department' => 'productionDepartment',
            'script_department' => 'scriptDepartment',
            'transportation_department' => 'transportationDepartment',
            'miscellaneous' => 'miscellaneous',
            'thanks' => 'thanks',
            'executive' => 'executive',
            'legal' => 'legal',
            'soundtrack' => 'soundtrack',
            'manager' => 'manager',
            'assistant' => 'assistant',
            'talent_agent' => 'talentAgent',
            'self' => 'self',
            'publicist' => 'publicist',
            'music_artist' => 'musicArtist',
            'podcaster' => 'podcaster',
            'archive_footage' => 'archiveFootage',
            'archive_sound' => 'archiveSound',
            'costume_supervisor' => 'costumeSupervisor',
            'hair_stylist' => 'hairStylist',
            'intimacy_coordinator' => 'intimacyCoordinator',
            'make_up_artist' => 'makeUpArtist',
            'music_supervisor' => 'musicSupervisor',
            'property_master' => 'propertyMaster',
            'script_supervisor' => 'scriptSupervisor',
            'showrunner' => 'showrunner',
            'stunt_coordinator' => 'stuntCoordinator',
            'accountant' => 'accountant'
        );
        if (empty($this->credits)) {
            foreach ($categoryIds as $categoryId) {
                $this->credits[$categoryId] = array();
            }
            $query = <<<EOF
category {
  id
}
title {
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
}
... on Crew {
  jobs {
    text
  }
}
EOF;
            $edges = $this->graphQlGetAll("Credits", "credits", $query);
            foreach ($edges as $edge) {
                $characters = array();
                if (!empty($edge->node->characters)) {
                    foreach ($edge->node->characters as $character) {
                        if (!empty($character->name)) {
                            $characters[] = $character->name;
                        }
                    }
                }
                $jobs = array();
                if (!empty($edge->node->jobs)) {
                    foreach ($edge->node->jobs as $job) {
                        if (!empty($job->text)) {
                            $jobs[] = $job->text;
                        }
                    }
                }
                $titleThumbImageUrl = null;
                $titleFullImageUrl = null;
                if (!empty($edge->node->title->primaryImage->url)) {
                    $img = str_replace('.jpg', '', $edge->node->title->primaryImage->url);
                    $titleFullImageUrl = $img . 'QL100_UX1000_.jpg';
                    $fullImageWidth = $edge->node->title->primaryImage->width;
                    $fullImageHeight = $edge->node->title->primaryImage->height;
                    $newImageWidth = 140;
                    $newImageHeight = 207;
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                    $titleThumbImageUrl = $img . $parameter;
                }
                $this->credits[$categoryIds[$edge->node->category->id]][] = array(
                    'titleId' => isset($edge->node->title->id) ?
                                       str_replace('tt', '', $edge->node->title->id) : null,
                    'titleName' => isset($edge->node->title->titleText->text) ?
                                         $edge->node->title->titleText->text : null,
                    'titleType' => isset($edge->node->title->titleType->text) ?
                                         $edge->node->title->titleType->text : null,
                    'year' => isset($edge->node->title->releaseYear->year) ?
                                    $edge->node->title->releaseYear->year : null,
                    'endYear' => isset($edge->node->title->releaseYear->endYear) ?
                                       $edge->node->title->releaseYear->endYear : null,
                    'characters' => $characters,
                    'jobs' => $jobs,
                    'titleFullImageUrl' => $titleFullImageUrl,
                    'titleThumbImageUrl' => $titleThumbImageUrl
                );
            }
        }
        return $this->credits;
    }

    #-------------------------------------------------[ Video ]---
    /**
     * Get all video URL's and images from videogallery page
     * @return categorized array videos
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
  name(id: \$id) {
    primaryVideos(first:9999) {
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
            $data = $this->graphql->query($query, "Video", ["id" => "nm$this->imdbID"]);
            foreach ($data->name->primaryVideos->edges as $edge) {
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

    #----------------------------------------------------------[ News ]---
    /**
     * Get news items about this name, max 100 items!
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
  name(id: \$id) {
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
            $data = $this->graphql->query($query, "News", ["id" => "nm$this->imdbID"]);
            foreach ($data->name->news->edges as $edge) {
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

    #========================================================[ photo/poster ]===
    /**
     * Setup cover photo (thumbnail and big variant)
     * @see IMDB page / (NamePage)
     */
    private function populatePoster()
    {
        $query = <<<EOF
query Poster(\$id: ID!) {
  name(id: \$id) {
    primaryImage {
      url
      width
      height
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Poster", ["id" => "nm$this->imdbID"]);
        if (!empty($data->name->primaryImage->url)) {
            $img = str_replace('.jpg', '', $data->name->primaryImage->url);

            // full image
            $this->mainPoster = $img . 'QL100_UX1000_.jpg';

            // thumb image
            if (!empty($data->name->primaryImage->width) && !empty($data->name->primaryImage->height)) {
                $fullImageWidth = $data->name->primaryImage->width;
                $fullImageHeight = $data->name->primaryImage->height;
                $newImageWidth = $this->config->namePhotoThumbnailWidth;
                $newImageHeight = $this->config->namePhotoThumbnailHeight;
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                $this->mainPosterThumb = $img . $parameter;
            }
        }
    }

    #-----------------------------------------[ Helper for Trivia, Quotes and Trademarks ]---
    /** Parse Trivia, Quotes and Trademarks
     * @param string $name
     * @param array $arrayName
     */
    protected function dataParse($name, $arrayName)
    {
        $query = <<<EOF
text {
  plainText
}
EOF;
        $data = $this->graphQlGetAll("Data", $name, $query);
        foreach ($data as $edge) {
            if (!empty($edge->node->text->plainText)) {
                $arrayName[] = $edge->node->text->plainText;
            }
        }
        return $arrayName;
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
        $filter = ', filter: {relationshipTypes: ' . $name . '}';
        $query = <<<EOF
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
EOF;
        $data = $this->graphQlGetAll("Data", "relations", $query, $filter);
        foreach ($data as $edge) {
            if (empty($edge->node->relationName->name) && empty($edge->node->relationName->nameText)) {
                continue;
            }
            if (!empty($edge->node->relationName->name)) {
                $id = isset($edge->node->relationName->name->id) ?
                            str_replace('nm', '', $edge->node->relationName->name->id) : null;
                $name = isset($edge->node->relationName->name->nameText->text) ?
                              $edge->node->relationName->name->nameText->text : null;
            } else {
                $id = null;
                $name = isset($edge->node->relationName->nameText) ?
                              $edge->node->relationName->nameText : null;
            }
            $arrayName[] = array(
                'imdb' => $id,
                'name' => $name,
                'relType' => isset($edge->node->relationshipType->text) ?
                                   $edge->node->relationshipType->text : null
            );
        }
        return $arrayName;
    }

    #-----------------------------------------------------------[ Other Publicity Listings helper]---
    /** helper for Article, Interview, Magazine and Pictorial publicity listings about this person
     * @return array listing
     * @see IMDB person page /publicity
     */
    protected function pubOtherListing($listingType)
    {
        $results = array();
        $filter = ', filter: {categories: ["' . lcfirst($listingType) . '"]}';
        $query = <<<EOF
... on $listingType {
  authors {
    plainText
  }
  publication
  reference
  date
  region {
    id
  }
  title {
    text
  }
}
EOF;
        $data = $this->graphQlGetAll($listingType, "publicityListings", $query, $filter);
        foreach ($data as $edge) {
            $date = array(
                'day' => isset($edge->node->date->day) ?
                               $edge->node->date->day : null,
                'month' => isset($edge->node->date->month) ?
                                 $edge->node->date->month : null,
                'year' => isset($edge->node->date->year) ?
                                $edge->node->date->year : null
            );
            $authors = array();
            if (!empty($edge->node->authors)) {
                foreach ($edge->node->authors as $author) {
                    if (!empty($author->plainText)) {
                        $authors[] = $author->plainText;
                    }
                }
            }
            $results[] = array(
                'publication' => isset($edge->node->publication) ?
                                       $edge->node->publication : null,
                'regionId' => isset($edge->node->region->id) ?
                                    $edge->node->region->id : null,
                'title' => isset($edge->node->title->text) ?
                                 $edge->node->title->text : null,
                'date' => $date,
                'reference' => isset($edge->node->reference) ?
                                     $edge->node->reference : null,
                'authors' => $authors
            );
        }
        return $results;
    }

    #-----------------------------------------[ Helper GraphQL Paginated ]---
    /**
     * Get all edges of a field in the name type
     * @param string $queryName The cached query name
     * @param string $fieldName The field on name you want to get
     * @param string $nodeQuery Graphql query that fits inside node { }
     * @param string $filter Add's extra Graphql query filters like categories
     * @return \stdClass[]
     */
    protected function graphQlGetAll($queryName, $fieldName, $nodeQuery, $filter = '')
    {
        $query = <<<EOF
query $queryName(\$id: ID!, \$after: ID) {
  name(id: \$id) {
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
            $data = $this->graphql->query($fullQuery, $queryName, ["id" => "nm$this->imdbID", "after" => $endCursor]);
            $edges = array_merge($edges, $data->name->{$fieldName}->edges);
            $hasNextPage = $data->name->{$fieldName}->pageInfo->hasNextPage;
            $endCursor = $data->name->{$fieldName}->pageInfo->endCursor;
        }
        return $edges;
    }

    #----------------------------------------------------------[ imdbID redirect ]---
    /**
     * Check if imdbid is redirected to another id or not.
     * It sometimes happens that imdb redirects an existing id to a new id.
     * If user uses search class this check isn't nessecary as the returned results already contain a possible new imdbid
     * @var $this->imdbID The imdbid used to call this class
     * @var $nameImdbId the returned imdbid from Graphql call (in some cases this can be different)
     * @return $nameImdbId (the new redirected imdbId) or false (no redirect)
     * @see IMDB page / (TitlePage)
     */
    public function checkRedirect()
    {
        $query = <<<EOF
query Redirect(\$id: ID!) {
  name(id: \$id) {
    meta {
      canonicalId
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Redirect", ["id" => "nm$this->imdbID"]);
        $nameImdbId = str_replace('nm', '', $data->name->meta->canonicalId);
        if ($nameImdbId  != $this->imdbID) {
            // todo write to log?
            return $nameImdbId;
        } else {
            return false;
        }
    }

    #----------------------------------------------------------[ Award filter helper ]---
    /**
     * Build award filter string
     * @param $winsOnly boolean
     * @param $event string eventId
     * @return string $filter
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

}
