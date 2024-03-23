<?php

#############################################################################
# IMDBPHP6                                        (c) Ed (github: duck7000) #
# written & maintained by Ed                                                #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

class TitleSearchAdvanced extends MdbBase
{

    /**
     * Advanced Search IMDb on genres, titleTypes, creditId, startDate, endDate, countryId, languageId
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
     * @param string $creditId works only with nameID like "nm0001228" (Peter Fonda)
     *
     * @param string $startDate search from startDate til present date, iso date ("1975-01-01")
     * @param string $endDate search from endDate and earlier, iso date ("1975-01-01")
     * if both dates are provided searches within the date span ("1950-01-01" - "1980-01-01")
     *
     * @param string $countryId iso 3166 country code like "US" or "US,DE" (separate by comma)
     * 
     * @param string $languageId iso 639 Language code like "en" or "en,de" (separate by comma)
     *
     * @return Title[] array of Titles
     * array[]
     *      ['imdbid']          string      imdbid from the found title
     *      ['originalTitle']   string      originalTitle from the found title
     *      ['title']           string      title from the found title
     *      ['year']            string      year or year span from the found title
     *      ['movietype']       string      titleType from the found title
     */
    public function advancedSearch(
        $searchTerm = '',
        $genres = '',
        $types = '',
        $creditId = '',
        $startDate = '',
        $endDate = '',
        $countryId = '',
        $languageId = ''
    )
    {

        $amount = $this->config->titleSearchAdvancedAmount;
        $sortBy = $this->config->sortBy;
        $sortOrder = $this->config->sortOrder;

        $results = array();

        // check and validate input parameters
        $inputSearchTerm = $this->checkSearchTerm($searchTerm);
        $inputGenres = $this->checkItems($genres);
        $inputTypes = $this->checkItems($types);
        $inputCreditId = $this->checkItems($creditId);
        $inputReleaseDates = $this->checkReleaseDates($startDate, $endDate);
        $inputCountryId = $this->checkItems($countryId);
        $inputLanguageId = $this->checkItems($languageId);
        // check releasedate valid or not, array() otherwise
        if ($inputReleaseDates === false) {
            return $results;
        }

        // check if there is at least one valid input parameter, array() otherwise
        if ($inputSearchTerm == "null" &&
            empty($inputGenres) &&
            empty($inputTypes) &&
            empty($inputCreditId) &&
            $inputReleaseDates["startDate"] == "null" &&
            $inputReleaseDates["endDate"] == "null" &&
            empty($inputCountryId) &&
            empty($inputLanguageId)
            )
        {
            return $results;
        }

        $query = <<<EOF
query advancedSearch{
  advancedTitleSearch(
    first: $amount, sort: {sortBy: $sortBy sortOrder: $sortOrder}
    constraints: {
      titleTextConstraint: {searchTerm: $inputSearchTerm}
      genreConstraint: {allGenreIds: [$inputGenres]}
      titleTypeConstraint: {anyTitleTypeIds: [$inputTypes]}
      releaseDateConstraint: {releaseDateRange: {start: $inputReleaseDates[startDate] end: $inputReleaseDates[endDate]}}
      creditedNameConstraint: {anyNameIds: [$inputCreditId]}
      originCountryConstraint: {anyCountries: [$inputCountryId]}
      languageConstraint: {anyLanguages: [$inputLanguageId]}
      explicitContentConstraint: {explicitContentFilter: INCLUDE_ADULT}
    }
  ) {
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
        }
      }
    }
  }
}
EOF;
        $queryStripped = implode("\n", array_map('trim', explode("\n", $query)));
        $data = $this->graphql->query($queryStripped, "advancedSearch");
        foreach ($data->advancedTitleSearch->edges as $edge) {
            $imdbId = isset($edge->node->title->id) ? str_replace('tt', '', $edge->node->title->id) : '';
            $originalTitle = isset($edge->node->title->titleText->text) ? $edge->node->title->titleText->text : '';
            $title = isset($edge->node->title->titleText->text) ? $edge->node->title->titleText->text : '';
            $movietype = isset($edge->node->title->titleType->text) ? $edge->node->title->titleType->text : '';
            $yearRange = '';
            if (isset($edge->node->title->releaseYear->year)) {
                $yearRange .= $edge->node->title->releaseYear->year;
                if (isset($edge->node->title->releaseYear->endYear)) {
                    $yearRange .= '-' . $edge->node->title->releaseYear->endYear;
                }
            }
            $results[] = array(
                'imdbid' => $imdbId,
                'originalTitle' => $originalTitle,
                'title' => $title,
                'year' => $yearRange,
                'movietype' => $movietype
            );
        }
        return $results;
    }

    #========================================================[ Helper functions]===

    /**
     * Check if there is at least one, possible more input items
     * @param string $items if multiple items separate by , (Horror,Action etc)
     * @return $items double quoted and separated by comma if more then one
     */
    private function checkItems($items)
    {
        if (empty(trim($items))) {
            return '';
        }
        if (stripos($items, ',') !== false) {
            $itemsParts = explode(",", $items);
            $itemsOutput = '"';
            foreach ($itemsParts as $key => $value) {
                $itemsOutput .= trim($value);
                end($itemsParts);
                if ($key !== key($itemsParts)) {
                    $itemsOutput .= '","';
                } else {
                    $itemsOutput .= '"';
                }
                
            }
            return $itemsOutput;
        } else {
            return '"' . trim($items) . '"';
        }
    }

    /**
     * Check searchTerm
     * @param string $searchTerm
     * @return $searchTerm or null double quoted
     */
    private function checkSearchTerm($searchTerm)
    {
        if (empty(trim($searchTerm))) {
            return "null";
        } else {
            return '"' . trim($searchTerm) . '"';
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
     * Check if input date is not empty and valid
     * @param string $startDate (searches between startDate and present date) iso date string ('1975-01-01')
     * @param $endDate (searches between endDate and earlier) iso date string ('1975-01-01')
     * @return array startDate|string, endDate|string or null
     */
    private function checkReleaseDates($startDate, $endDate)
    {
        if (empty(trim($startDate)) && empty(trim($endDate))) {
            return array(
                'startDate' => "null",
                'endDate' => "null"
                );
        }
        if (!empty(trim($startDate)) && !empty(trim($endDate))) {
            if ($this->validateDate($startDate) !== false && $this->validateDate($endDate) !== false) {
                return array(
                    'startDate' => '"' . trim($startDate) . '"',
                    'endDate' => '"' . trim($endDate) . '"'
                    );
            } else {
                return false;
            }
        } else {
            if (!empty(trim($startDate))) {
                if ($this->validateDate($startDate) !== false) {
                    return array(
                        'startDate' => '"' . trim($startDate) . '"',
                        'endDate' => "null"
                        );
                } else {
                    return false;
                }
            } else {
                if ($this->validateDate($endDate) !== false) {
                    return array(
                        'startDate' => "null",
                        'endDate' => '"' . trim($endDate) . '"'
                        );
                } else {
                    return false;
                }
            }
        }
    }
}
