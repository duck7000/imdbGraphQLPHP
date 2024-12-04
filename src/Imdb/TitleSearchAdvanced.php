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
 * Title Search Advanced Class for advanced searches
 * @author Ed (github user: duck7000)
 */
class TitleSearchAdvanced extends MdbBase
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
        $this->newImageWidth = $this->config->titleSearchAdvancedThumbnailWidth;
        $this->newImageHeight = $this->config->titleSearchAdvancedThumbnailHeight;
    }
    /**
     * Advanced Search IMDb on genres, titleTypes, creditId, startDate, endDate, countryId, languageId, $keywords
     * 
     * @param string $searchTerm input searchTerm to search for specific titleText
     *
     * @param string $genres if multiple genres separate by , (Horror,Action etc)
     * GenreIDs: Action, Adult, Adventure, Animation, Biography, Comedy, Crime,
     *           Documentary, Drama, Family, Fantasy, Film-Noir, Game-Show,
     *           History, Horror, Music, Musical, Mystery, News, Reality-TV,
     *           Romance, Sci-Fi, Short, Sport, Talk-Show, Thriller, War, Western
     *
     * @param string $types if multiple types separate by , (movie,tvSeries etc)
     * TitleTypeIDs: movie, tvSeries, short, tvEpisode, tvMiniSeries, tvMovie, tvSpecial,
     *               tvShort, videoGame, video, musicVideo, podcastSeries, podcastEpisode
     *
     * @param string $creditId works only with nameID like "0001228" (without nm) (Peter Fonda)
     *
     * @param string $startDate search from startDate til present date, iso date ("1975-01-01")
     * @param string $endDate search from endDate and earlier, iso date ("1975-01-01")
     * if both dates are provided searches within the date span ("1950-01-01" - "1980-01-01")
     * if one or both dates are not valid then the whole constraint will not be added!
     *
     * @param string $countryId iso 3166 country code like "US" or "US,DE" (separate by comma)
     * 
     * @param string $languageId iso 639 Language code like "en" or "en,de" (separate by comma)
     * 
     * @param string $keywords like "sex" or "sex,drugs" (separate by comma)
     * 
     * @param string $companyId like "0185428" (without co) (single companyid is supported)
     *
     * @return Title[] array of Titles
     * array[]
     *      ['imdbid']          string      imdbid from the found title
     *      ['originalTitle']   string      originalTitle from the found title
     *      ['title']           string      title from the found title
     *      ['year']            string      year or year span from the found title
     *      ['movietype']       string      titleType from the found title
     *      [runtime] =>        string      In seconds!
     *      [rating] =>         float
     *      [voteCount] =>      int
     *      [metacritic] =>     int
     *      [plot] =>           string
     *      [imgUrl] =>         string
     */
    public function advancedSearch(
        $searchTerm = '',
        $genres = '',
        $types = '',
        $creditId = '',
        $startDate = '',
        $endDate = '',
        $countryId = '',
        $languageId = '',
        $keywords = '',
        $companyId = ''
    )
    {

        $results = array();
        $titles = array();
        $constraints = $this->buildConstraints(
            $searchTerm,
            $genres,
            $types,
            $creditId,
            $startDate,
            $endDate,
            $countryId,
            $languageId,
            $keywords,
            $companyId
        );
        if (empty($constraints)) {
            return $results;
        }

        $amount = $this->config->titleSearchAdvancedAmount;
        $sortBy = $this->config->sortBy;
        $sortOrder = $this->config->sortOrder;

        $query = <<<EOF
query advancedSearch{
  advancedTitleSearch(
    first: $amount, sort: {sortBy: $sortBy sortOrder: $sortOrder}
    constraints: $constraints
  ) {
    total
    edges {
      node{
        title {
          id
          originalTitleText {
            text
          }
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
          runtime {
            seconds
          }
          ratingsSummary {
            aggregateRating
            voteCount
          }
          plot {
            plotText {
              plainText
            }
          }
          metacritic {
            metascore {
              score
            }
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "advancedSearch");
        foreach ($data->advancedTitleSearch->edges as $edge) {

            // Year range
            $yearRange = '';
            if (isset($edge->node->title->releaseYear->year)) {
                $yearRange .= $edge->node->title->releaseYear->year;
                if (isset($edge->node->title->releaseYear->endYear)) {
                    $yearRange .= '-' . $edge->node->title->releaseYear->endYear;
                }
            }

            // image url
            $imgUrl = null;
            if (!empty($edge->node->title->primaryImage->url)) {
                $fullImageWidth = $edge->node->title->primaryImage->width;
                $fullImageHeight = $edge->node->title->primaryImage->height;
                $img = str_replace('.jpg', '', $edge->node->title->primaryImage->url);
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                $imgUrl = $img . $parameter;
            }

            $titles[] = array(
                'imdbid' => isset($edge->node->title->id) ?
                                  str_replace('tt', '', $edge->node->title->id) : null,
                'originalTitle' => isset($edge->node->title->titleText->text) ?
                                         $edge->node->title->titleText->text : null,
                'title' => isset($edge->node->title->titleText->text) ?
                                 $edge->node->title->titleText->text : null,
                'year' => $yearRange,
                'movietype' => isset($edge->node->title->titleType->text) ?
                                     $edge->node->title->titleType->text : null,
                'runtime' => isset($edge->node->title->runtime->seconds) ?
                                   $edge->node->title->runtime->seconds : null,
                'rating' => isset($edge->node->title->ratingsSummary->aggregateRating) ?
                                  $edge->node->title->ratingsSummary->aggregateRating : null,
                'voteCount' => isset($edge->node->title->ratingsSummary->voteCount) ?
                                     $edge->node->title->ratingsSummary->voteCount : null,
                'metacritic' => isset($edge->node->title->metacritic->metascore->score) ?
                                      $edge->node->title->metacritic->metascore->score : null,
                'plot' => isset($edge->node->title->plot->plotText->plainText) ?
                                $edge->node->title->plot->plotText->plainText : null,
                'imgUrl' => $imgUrl
            );
        }
        if (!empty($titles)) {
            $results = array(
                'totalFoundResults' => isset($data->advancedNameSearch->total) ?
                                             $data->advancedNameSearch->total : null,
                'titles' => $titles
            );
        }
        return $results;
    }

    #========================================================[ Helper functions]===

    /**
     * Check input parameters and build constraints
     * @param string $searchTerm
     * @param string $genres
     * @param string $types
     * @param string $creditId
     * @param string $startDate
     * @param string $endDate
     * @param string $countryId
     * @param string $languageId
     * @param string $keywords
     * @param string $companyId
     * @return string constraints or false
     */
    private function buildConstraints(
        $searchTerm,
        $genres,
        $types,
        $creditId,
        $startDate,
        $endDate,
        $countryId,
        $languageId,
        $keywords,
        $companyId
    )
    {
        $constraint = '{';

        // Title search input
        if (!empty(trim($searchTerm))) {
            $constraint .= 'titleTextConstraint:{searchTerm:"' . $searchTerm . '"}';
        }

        // Genres, Input is array
        $checkedGenres = $this->checkItems($genres);
        if ($checkedGenres !== false) {
            $constraint .= 'genreConstraint:{allGenreIds:["' . $checkedGenres . '"]}';
        }

        // Types, Input is array
        $checkedTypes = $this->checkItems($types);
        if ($checkedTypes !== false) {
            $constraint .= 'titleTypeConstraint:{anyTitleTypeIds:["' . $checkedTypes . '"]}';
        }

        // CreditId, Input is array
        if (!empty($creditId)) {
            $creditId = "nm$creditId";
        }
        $checkedCreditId = $this->checkItems($creditId);
        if ($checkedCreditId !== false) {
            $constraint .= 'creditedNameConstraint:{anyNameIds:["' . $checkedCreditId . '"]}';
        }

        // Date Range
        $dateRange = $this->checkDates( $startDate, $endDate);
        if ($dateRange !== false) {
            $constraint .= $dateRange;
        }

        // CountryId, Input is array
        $checkedCountryId = $this->checkItems($countryId);
        if ($checkedCountryId !== false) {
            $constraint .= 'originCountryConstraint:{anyCountries:["' . $checkedCountryId . '"]}';
        }

        // LanguageId, Input is array
        $checkedLanguageId = $this->checkItems($languageId);
        if ($checkedLanguageId !== false) {
            $constraint .= 'languageConstraint:{anyLanguages:["' . $checkedLanguageId . '"]}';
        }

        // Keywords, Input is array
        $checkedKeywords = $this->checkItems($keywords);
        if ($checkedKeywords !== false) {
            $constraint .= 'keywordConstraint:{anyKeywords:["' . $checkedKeywords . '"]}';
        }

        // CompanyId, Input is array
        if (!empty($companyId)) {
            $companyId = "co$companyId";
        }
        $checkedCompanyId = $this->checkItems($companyId);
        if ($checkedCompanyId !== false) {
            $constraint .= 'creditedCompanyConstraint:{anyCompanyIds:["' . $checkedCompanyId . '"]}';
        }

        if ($constraint == '{') {
            return false;
        }

        // Adult constraint included
        $constraint .= 'explicitContentConstraint:{explicitContentFilter:INCLUDE_ADULT}';

        // end constraints
        $constraint .= '}';

        return $constraint;
    }

    /**
     * Check if there is at least one, possible more input items
     * @param string $items if multiple items separate by , (Horror,Action etc)
     * @return $items double quoted and separated by comma if more then one
     */
    private function checkItems($items)
    {
        if (empty(trim($items))) {
            return false;
        }
        if (stripos($items, ',') !== false) {
            $itemsParts = explode(",", $items);
            $itemsOutput = '';
            foreach ($itemsParts as $key => $value) {
                $itemsOutput .= trim($value);
                end($itemsParts);
                if ($key !== key($itemsParts)) {
                    $itemsOutput .= '","';
                }
            }
            return $itemsOutput;
        } else {
            return trim($items);
        }
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
     * Check if input dates not empty and valid
     * @param string $startDate (searches between startDate and present date) iso date string ('1975-01-01')
     * @param $endDate (searches between endDate and earlier) iso date string ('1975-01-01')
     * @return string constraints or false
     */
    private function checkDates($startDate, $endDate)
    {
        if (!empty($startDate) || !empty($endDate)) {
            $constraint = 'releaseDateConstraint:{';
            if (!empty($startDate) && !empty($endDate)) {
                if ($this->validateDate($startDate) !== false && $this->validateDate($endDate) !== false) {
                    $constraint .= 'releaseDateRange:{start:"' . $startDate . '"end:"' . $endDate . '"}}';
                } else {
                    return false;
                }
            } else {
                if (!empty($startDate) && $this->validateDate($startDate) !== false) {
                    $constraint .= 'releaseDateRange:{start:"' . $startDate . '"}}';
                } else {
                    if ($this->validateDate($endDate) !== false) {
                        $constraint .= 'releaseDateRange:{end:"' . $endDate . '"}}';
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
