<?php

#############################################################################
# imdbGraphQLPHP Company                 https://www.imdb.com/trailers/     #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;

/**
 * Obtains information about Company
 * This info is only available for imdbPro users but throught GraphQL it is freely available!
 * @author Ed (github user: duck7000)
 */
class Company extends MdbBase
{

    /**
     * @param Config $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(Config $config = null, LoggerInterface $logger = null, CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
    }

    /**
     * Get all info about a specific company (only freely available info)
     * PrimaryImage and bio is available but returns always null so those are not included.
     * @return
     * Array
     *   (
     *      [0] => Array
     *              [id] =>             (string) (without co)
     *              [name] =>           (string)
     *              [country] =>        (string)
     *              [meterRanking] =>   (array)
     *                  [currentRank] =>        (int) 3622
     *                  [changeDirection] =>    (string) DOWN
     *                  [difference] =>         (int) 1707
     *              [type] =>           (array)
     *                  [0] => (string) Distributor
     *                  [1] => (string) Production
     *              [keyStaff] =>       (array)
     *                  [0] => (array)
     *                      [id] =>     (string) 3280279
     *                      [name] =>   (string) Ciaran Michael Vejby
     *                      [employments] => (array)
     *                          [0] => (array)
     *                              [employmentTitle] => (string) Editorial Department
     *                              [occupation] =>      (string) Editorial Department
     *                              [branch] =>          (string)
     *              [knownFor] =>       (array)
     *                  [0] => (array)
     *                      [id] =>     (string) 0993840
     *                      [name] =>   (string) Army of the dead
     *                      [jobs] =>   (array)
     *                          [0] => (array)
     *                              [category] => (string) Other Companies
     *                              [job] =>      (string) Music
     *                      [countries] =>   (array)
     *                          [0] => World-wide
     *                      [year]      (int)
     *                      [endYear]   (int)
     *              [affiliations] =>   (array)
     *                  [0] (array)
     *                      [companyName] => (string) Warner Bros. Entertainment
     *                      [description] => (string) Also Known As
     *  )
     */
    public function companyInfo($companyId)
    {
        $companyResults = array();
        $query = <<<EOF
query Company {
  company(id: "$companyId") {
    id
    companyText {
      text
    }
    country {
      text
    }
    companyTypes {
      text
    }
    keyStaff(first: 9999) {
      edges {
        node {
          name {
            id
            nameText {
              text
            }
          }
          summary {
            employment(limit: 9999) {
              title {
                text
              }
              branch {
                text
              }
              occupation {
                text
              }
            }
          }
        }
      }
    }
    knownForTitles(first: 9999) {
      edges {
        node {
          title {
            id
            titleText {
              text
            }
          }
          summary {
            countries {
              text
            }
            jobs {
              category {
                text
              }
              job {
                text
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
    affiliations(first: 9999) {
      edges {
        node {
          company {
            companyText {
              text
            }
          }
          text
        }
      }
    }
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
        $data = $this->graphql->query($query, "Company");

        //CompanyTypes
        $companyTypes = array();
        if (!empty($data->company->companyTypes)) {
            foreach ($data->company->companyTypes as $companyType) {
                if (!empty($companyType->text)) {
                    $companyTypes[] = $companyType->text;
                }
            }
        }

        // KeyStaff
        $keyStaff = array();
        if (!empty($data->company->keyStaff->edges)) {
            foreach ($data->company->keyStaff->edges as $staff) {

                // Employments
                $employments = array();
                if (!empty($staff->node->summary->employment)) {
                    foreach ($staff->node->summary->employment as $list) {
                        $employments[] = array(
                            'employmentTitle' => isset($list->title->text) ?
                                                       $list->title->text : null,
                            'occupation' => isset($list->occupation->text) ?
                                                  $list->occupation->text : null,
                            'branch' => isset($list->branch->text) ?
                                              $list->branch->text : null
                        );
                    }
                }
                $keyStaff[] = array(
                    'id' => isset($staff->node->name->id) ?
                                  str_replace('nm', '', $staff->node->name->id) : null,
                    'name' => isset($staff->node->name->nameText->text) ?
                                    $staff->node->name->nameText->text : null,
                    'employments' => $employments
                );
            }
        }

        // KnownFor
        $knownFor = array();
        if (!empty($data->company->knownForTitles->edges)) {
            foreach ($data->company->knownForTitles->edges as $title) {

                // Jobs
                $jobs = array();
                if (!empty($title->node->summary->jobs)) {
                    foreach ($title->node->summary->jobs as $job) {
                        $jobs[] = array(
                            'category' => isset($job->category->text) ?
                                                $job->category->text : null,
                            'job' => isset($job->job->text) ?
                                           $job->job->text : null
                        );
                    }
                }

                // Countries
                $countries = array();
                if (!empty($title->node->summary->countries)) {
                    foreach ($title->node->summary->countries as $country) {
                        if (!empty($country->text)) {
                            $countries[] = $country->text;
                        }
                    }
                }
                $knownFor[] = array(
                    'id' => isset($title->node->title->id) ?
                                  str_replace('tt', '', $title->node->title->id) : null,
                    'name' => isset($title->node->title->titleText->text) ?
                                    $title->node->title->titleText->text : null,
                    'jobs' =>  $jobs,
                    'countries' => $countries,
                    'year' => isset($title->node->summary->yearRange->year) ?
                                    $title->node->summary->yearRange->year : null,
                    'endYear' => isset($title->node->summary->yearRange->endYear) ?
                                       $title->node->summary->yearRange->endYear : null
                );
            }
        }

        //Affiliations
        $affiliations = array();
        if (!empty($data->company->affiliations->edges)) {
            foreach ($data->company->affiliations->edges as $affiliation) {
                    $affiliations[] = array(
                        'companyName' => isset($affiliation->node->company->companyText->text) ?
                                               $affiliation->node->company->companyText->text : null,
                        'description' => isset($affiliation->node->text) ?
                                               $affiliation->node->text : null
                    );
            }
        }

        //MeterRanking
        $meterRanking = array();
        if (!empty($data->company->meterRanking->currentRank)) {
            $meterRanking = array(
                'currentRank' => $data->company->meterRanking->currentRank,
                'changeDirection' => isset($data->company->meterRanking->rankChange->changeDirection) ?
                                           $data->company->meterRanking->rankChange->changeDirection : null,
                'difference' => isset($data->company->meterRanking->rankChange->difference) ?
                                      $data->company->meterRanking->rankChange->difference : null
            );
        }

        // Results
        $companyResults = array(
            'id' => isset($data->company->id) ?
                          str_replace('co', '', $data->company->id) : null,
            'name' => isset($data->company->companyText->text) ?
                            $data->company->companyText->text : null,
            'country' => isset($data->company->country->text) ?
                               $data->company->country->text : null,
            'meterRanking' => $meterRanking,
            'type' => $companyTypes,
            'keyStaff' => $keyStaff,
            'knownFor' => $knownFor,
            'affiliations' => $affiliations
        );
        return $companyResults;
    }

}
