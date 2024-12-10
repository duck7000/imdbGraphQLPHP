<?php

#############################################################################
# imdbGraphQLPHP                                ed (github user: duck7000)  #
# written by ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Company Search Class
 * @author Ed (github user: duck7000)
 */
class CompanySearch extends MdbBase
{

    /**
     * Search IMDb for companies matching input search string
     * @param string $company input company, ("warner brothers")
     * The results can be used as input for advancedTitleSearch class to get titles based on this company
     * Or the results can be used as input for Company class to get company info
     * 
     * @return array[]
     * Array
     * (
     *      [id] =>         (string) 0185428
     *      [name] =>       (string) Warner Brothers Entertainment
     *      [country] =>    (string) United States
     *      [type] =>       (string) Distributor
     * )
     */
    public function searchCompany($company)
    {
        $results = array();

        // check if $company is empty, return empty array
        if (empty(trim($company))) {
            return $results;
        }
        $inputCompany = '"' . trim($company) . '"';
        $amount = $this->config->companySearchAmount;

        $query = <<<EOF
query CompanySearch {
  mainSearch(
    first: $amount
    options: {
      searchTerm: $inputCompany
      type: COMPANY
      includeAdult: true
    }
  ) {
    edges {
      node {
        entity {
          ... on Company {
            id
            country {
              text
            }
            companyText {
              text
            }
            companyTypes(limit: 1) {
              text
            }
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "CompanySearch");
        foreach ($data->mainSearch->edges as $key => $edge) {
            $results[] = array(
                'id' => isset($edge->node->entity->id) ?
                              str_replace('co', '', $edge->node->entity->id) : null,
                'name' => isset($edge->node->entity->companyText->text) ?
                                $edge->node->entity->companyText->text : null,
                'country' => isset($edge->node->entity->country->text) ?
                                   $edge->node->entity->country->text : null,
                'type' => isset($edge->node->entity->companyTypes[0]->text) ?
                                $edge->node->entity->companyTypes[0]->text : null
            );
        }
        return $results;
    }

}
