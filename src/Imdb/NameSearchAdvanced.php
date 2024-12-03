<?php

#############################################################################
# imdbGraphQLPHP                                  (c) Ed (github: duck7000) #
# written & maintained by Ed                                                #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * Name Search Advanced Class for advanced searches
 * @author Ed (github user: duck7000)
 */
class NameSearchAdvanced extends MdbBase
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
        $this->newImageWidth = $this->config->nameSearchAdvancedThumbnailWidth;
        $this->newImageHeight = $this->config->nameSearchAdvancedThumbnailHeight;
    }

    /**
     * Advanced Name Search
     * 
     * @param string $searchTerm input searchTerm to search with specific name
     *
     * @param string $birthDay like (month-day iso) "--11-19" or "11-19" (both are supported)
     *
     * @param string $birthDateRangeStart start date (iso date string) ("1975-01-01")
     * @param string $birthDateRangeEnd end date (iso date string) ("1995-01-01")
     * if both dates are provided searches within the date span ("1950-01-01" - "1980-01-01")
     * 
     * @param string $deathDateRangeStart start date (iso date string) ("1975-01-01")
     * @param string  $deathDateRangeEnd end date (iso date string) ("1995-01-01")
     * if both dates are provided searches within the date span ("1950-01-01" - "1980-01-01")
     *
     * @param string $birthPlace input searchTerm to search names with specific birth places
     *
     * @return array of Names
     * array[]
     *  [totalFoundResults] => (int) 2 Total found from search result,
     *      this will not be the total results from this method as it is limited by config setting nameSearchAdvancedAmount
     *  [names] => Array()
     *      [0] => Array()
     *          [imdbid] => (string) 0001228 (without nm)
     *          [name] =>   (string) Peter Fonda
     *          [bio] =>    (string) Name bio text
     *          [professions] => Array()
     *              [0] => (string) Actor
     *              [1] => (string) Director
     *              [2] => (string) Writer
     *          [knownFor] => Array()
     *              [0] => Array()
     *                  [titleId] =>    (string) 0064276 (without tt)
     *                  [title] =>      (string) Easy Rider
     *                  [year] =>       (int) 1969
     *                  [endYear] =>    (int) (will be null if not available)
     *          [imgUrl] => (string) ImageUrl for thumbnail
     */
    public function advancedNameSearch(
        $searchTerm = '',
        $birthDay = '',
        $birthDateRangeStart = '',
        $birthDateRangeEnd = '',
        $deathDateRangeStart = '',
        $deathDateRangeEnd = '',
        $birthPlace = ''
    )
    {

        $results = array();
        $names = array();
        $constraints = $this->buildConstraints(
            $searchTerm,
            $birthDay,
            $birthDateRangeStart,
            $birthDateRangeEnd,
            $deathDateRangeStart,
            $deathDateRangeEnd,
            $birthPlace
        );
        if (empty($constraints)) {
            return $results;
        }
        
        $amount = $this->config->nameSearchAdvancedAmount;
        $sortBy = $this->config->nameSortBy;
        $sortOrder = $this->config->nameSortOrder;

        $query = <<<EOF
query AdvancedNameSearch {
  advancedNameSearch(
    first: $amount, sort: {sortBy: $sortBy sortOrder: $sortOrder}
    constraints: $constraints
  ) {
    total
    edges {
      node{
        name {
          id
          nameText {
            text
          }
          primaryProfessions {
            category {
              text
            }
          }
          bio {
            text {
              plainText
            }
          }
          knownFor(first: 1) {
            edges {
              node {
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
        $data = $this->graphql->query($query, "AdvancedNameSearch");
        foreach ($data->advancedNameSearch->edges as $edge) {

            // professions
            $professions = array();
            if (!empty($edge->node->name->primaryProfessions)) {
                foreach ($edge->node->name->primaryProfessions as $profession) {
                    if (!empty($profession->category->text)) {
                        $professions[] = $profession->category->text;
                    }
                }
            }

            // knownFor
            $knownFor = array();
            if (!empty($edge->node->name->knownFor->edges)) {
                foreach ($edge->node->name->knownFor->edges as $known) {
                    $knownFor[] = array(
                        'titleId' => isset($known->node->credit->title->id) ?
                                           str_replace('tt', '', $known->node->credit->title->id) : null,
                        'title' => isset($known->node->credit->title->titleText->text) ?
                                         $known->node->credit->title->titleText->text : null,
                        'year' => isset($known->node->credit->title->releaseYear->year) ?
                                        $known->node->credit->title->releaseYear->year : null,
                        'endYear' => isset($known->node->credit->title->releaseYear->endYear) ?
                                           $known->node->credit->title->releaseYear->endYear : null
                    );
                }
            }

            // image url
            $imgUrl = null;
            if (!empty($edge->node->name->primaryImage->url)) {
                $fullImageWidth = $edge->node->name->primaryImage->width;
                $fullImageHeight = $edge->node->name->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->node->name->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $imgUrl = $img . $parameter;
            }

            // Found names
            $names[] = array(
                'imdbid' => isset($edge->node->name->id) ?
                                  str_replace('nm', '', $edge->node->name->id) : null,
                'name' => isset($edge->node->name->nameText->text) ?
                                $edge->node->name->nameText->text : null,
                'bio' => isset($edge->node->name->bio->text->plainText) ?
                               $edge->node->name->bio->text->plainText : null,
                'professions' => $professions,
                'knownFor'=> $knownFor,
                'imgUrl' => $imgUrl
            );
        }
        if (!empty($names)) {
            $results = array(
                'totalFoundResults' => isset($data->advancedNameSearch->total) ?
                                             $data->advancedNameSearch->total : null,
                'names' => $names
            );
        }
        return $results;
    }

    #========================================================[ Helper functions]===

    /**
     * Check input parameters and build constraints
     * @param string $searchTerm
     * @param string $birthDay
     * @param string $birthDateRangeStart
     * @param string $birthDateRangeEnd
     * @param string $deathDateRangeStart
     * @param string $deathDateRangeEnd
     * @param string $birthPlace
     * @return string constraints or false
     */
    private function buildConstraints(
    $searchTerm,
    $birthDay,
    $birthDateRangeStart,
    $birthDateRangeEnd,
    $deathDateRangeStart,
    $deathDateRangeEnd,
    $birthPlace
    )
    {
        $constraint = '{';

        // Name search input
        if (!empty(trim($searchTerm))) {
            $constraint .= 'nameTextConstraint: {searchTerm: "' . $searchTerm . '"}';
        }

        // Birth Date Range and BirthDay
        $birthDateRange = $this->checkBirthDates($birthDateRangeStart, $birthDateRangeEnd, $birthDay);
        if ($birthDateRange !== false) {
            $constraint .= $birthDateRange;
        }

        // Death Date Range
        $deathDateRange = $this->checkDeathDates($deathDateRangeStart, $deathDateRangeEnd);
        if ($deathDateRange !== false) {
            $constraint .= $deathDateRange;
        }

        // Birth Place
        if (!empty(trim($birthPlace))) {
            $constraint .= ' birthPlaceConstraint: {birthPlace: "' . $birthPlace . '"}';
        }

        if ($constraint == '{') {
            return false;
        }

        // Adult constraint included
        $constraint .= 'explicitContentConstraint: {explicitContentFilter: INCLUDE_ADULT}';

        // end constraints
        $constraint .= '}';

        return $constraint;
    }

    /**
     * Check if provided date is valid
     * @param string $date input date
     * @return boolean true or false
     */
    private function validateDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Check if input birthday is not empty and valid
     * @param string $date iso date string ('--04-24')
     * @return $date|string
     */
    private function checkBirthDay($birthDate)
    {
        $parts = explode('-', $birthDate);
        if (count($parts) == 4) {
            return '--' . $parts[2] . '-' . $parts[3];
        } elseif (count($parts) == 2) {
            return '--' . $parts[0] . '-' . $parts[1];
        } else {
            return false;
        }
    }

    /**
     * Check if input birth dates not empty and valid
     * @param string $startDate (searches between startDate and present date) iso date string ('1975-01-01')
     * @param $endDate (searches between endDate and earlier) iso date string ('1975-01-01')
     * @param $birthDay input birhtday string like "--10-19" or "10-19"
     * @return string constraints or false
     */
    private function checkBirthDates($startDate, $endDate, $birthDay)
    {
        if (!empty($birthDay) || !empty($startDate) || !empty($endDate)) {
            $constraint = 'birthDateConstraint: {';
            if (!empty($startDate) && !empty($endDate)) {
                if ($this->validateDate($startDate) !== false && $this->validateDate($endDate) !== false) {
                    $constraint .= 'birthDateRange: {start:"' . $startDate . '"end:"' . $endDate . '"}';

                    //birthday?
                    if (!empty($birthDay) && $this->checkBirthDay($birthDay) !== false) {
                        $constraint .= 'birthday:"' . $birthDay . '"}';
                    } else {
                        $constraint .= '}';
                    }
                } else {
                    return false;
                }
            } else {
                if (!empty($startDate) && $this->validateDate($startDate) !== false) {
                    $constraint .= 'birthDateRange: {start:"' . $startDate . '"}';

                    //birthday?
                    if (!empty($birthDay) && $this->checkBirthDay($birthDay) !== false) {
                        $constraint .= 'birthday:"' . $birthDay . '"}';
                    } else {
                        $constraint .= '}';
                    }
                } else {
                    if ($this->validateDate($endDate) !== false) {
                        $constraint .= 'birthDateRange: {end:"' . $endDate . '"}';
                    }
                    // birthday only
                    if (!empty($birthDay) && $this->checkBirthDay($birthDay) !== false) {
                        $constraint .= 'birthday:"' . $birthDay . '"}';
                    } else {
                        return false;
                    }
                }
            }
            return $constraint;
        } else {
            return false;
        }
    }
    
    /**
     * Check if input death dates not empty and valid
     * @param string $startDate (searches between startDate and present date) iso date string ('1975-01-01')
     * @param $endDate (searches between endDate and earlier) iso date string ('1975-01-01')
     * @return string constraints or false
     */
    private function checkDeathDates($startDate, $endDate)
    {
        if (!empty($startDate) || !empty($endDate)) {
            $constraint = 'deathDateConstraint: {';
            if (!empty($startDate) && !empty($endDate)) {
                if ($this->validateDate($startDate) !== false && $this->validateDate($endDate) !== false) {
                    $constraint .= 'deathDateRange: {start:"' . $startDate . '"end:"' . $endDate . '"}}';
                } else {
                    return false;
                }
            } else {
                if (!empty($startDate) && $this->validateDate($startDate) !== false) {
                    $constraint .= 'deathDateRange: {start:"' . $startDate . '"}}';
                } else {
                    if ($this->validateDate($endDate) !== false) {
                        $constraint .= 'deathDateRange: {end:"' . $endDate . '"}}';
                    } else {
                        return false;
                    }
                }
            }
            return $constraint;
        } else {
            return false;
        }
    }

}
