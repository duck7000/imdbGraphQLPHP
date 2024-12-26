<?php

#############################################################################
# imdbGraphQLPHP                                ed (github user: duck7000)  #
# written by ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

class TitleSearch extends MdbBase
{

    /**
     * Search IMDb for titles matching $searchTerms
     * @param string $searchTerms
     * @param string $types input search types like "MOVIE" or "MOVIE,TV" (separate by comma if more then one)
     * Default for $types: null (search within all types)
     * Possible values for $types:
     *  MOVIE
     *  MUSIC_VIDEO
     *  PODCAST_EPISODE
     *  PODCAST_SERIES
     *  TV
     *  TV_EPISODE
     *  VIDEO_GAME
     * 
     * @param string $startDate search from startDate til present date, iso date (year-month-day) ("1975-01-01")
     * @param string $endDate search from endDate and earlier, iso date (year-month-day) ("1975-01-01")
     * if both dates are provided searches within the date span ("1950-01-01" - "1980-01-01")
     * 
     * @return array<int, array<string, string|Title>>
     */
    public function search($searchTerms, $types = null, $startDate = '', $endDate = '')
    {
        $amount = $this->config->titleSearchAmount;
        $results = array();
        $inputReleaseDates = $this->checkReleaseDates($startDate, $endDate);

        // check if $searchTerm is empty or releaseDates === false, return empty array
        if (empty(trim($searchTerms)) || $inputReleaseDates === false) {
            return $results;
        }

        $query = <<<EOF
query Search{
  mainSearch(
    first: $amount
    options: {
      searchTerm: "$searchTerms"
      type: TITLE
      includeAdult: true
      titleSearchOptions: {
        type: [$types]
        releaseDateRange: {
          start: $inputReleaseDates[startDate]
          end: $inputReleaseDates[endDate]
        }
      }
    }
  ) {
    edges {
      node{
        entity {
          ... on Title {
            id
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
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Search");
        foreach ($data->mainSearch->edges as $key => $edge) {
            $yearRange = null;
            if (isset($edge->node->entity->releaseYear->year)) {
                $yearRange .= $edge->node->entity->releaseYear->year;
                if (isset($edge->node->entity->releaseYear->endYear)) {
                    $yearRange .= '-' . $edge->node->entity->releaseYear->endYear;
                }
            }
            $id = isset($edge->node->entity->id) ?
                        str_replace('tt', '', $edge->node->entity->id) : null;
            $title = isset($edge->node->entity->titleText->text) ?
                           $edge->node->entity->titleText->text : null;
            $origTitle = isset($edge->node->entity->originalTitleText->text) ?
                               $edge->node->entity->originalTitleText->text : null;
            $movieType = isset($edge->node->entity->titleType->text) ?
                               $edge->node->entity->titleType->text : null;
            // return search results as Title object
            $return = Title::fromSearchResult(
                $id,
                $title,
                $origTitle,
                $yearRange,
                $movieType,
                $this->config,
                $this->logger,
                $this->cache
            );
            $results[] = array(
                'imdbid' => $id,
                'title' => $title,
                'originalTitle' => $origTitle,
                'year' => $yearRange,
                'movietype' => $movieType,
                'titleSearchObject' => $return
            );
        }
        return $results;
    }


    #========================================================[ Helper functions]===

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
