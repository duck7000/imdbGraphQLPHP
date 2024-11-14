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
 * Keyword Search Class
 * @author Ed (github user: duck7000)
 */
class KeywordSearch extends MdbBase
{

    /**
     * Search IMDb for titles matching $keywords
     * @param string $keywords input keywords, ("nihilism" or "sex drugs")
     *
     * @return array[]
     * Array
     * (
     *  [nihilism]
     *      [keywordId] => 0022341 (without kw)
     *      [totalTitles] => 517
     *  [reference to nihilism]
     *      [keywordId] => 0467796
     *      [totalTitles] => 1
     * )
     */
    public function searchKeyword($keywords)
    {
        $results = array();

        // check if $keywords is empty, return empty array
        if (empty(trim($keywords))) {
            return $results;
        }
        $inputKeywords = '"' . trim($keywords) . '"';
        $amount = $this->config->keywordSearchAmount;

        $query = <<<EOF
query Search {
  mainSearch(
    first: $amount
    options: {
      searchTerm: $inputKeywords
      type: KEYWORD
      includeAdult: true
    }
  ) {
    edges {
      node {
        entity {
          ... on Keyword {
            id
            text {
              text
            }
            titles(first: 9999) {
              total
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
            $keywordId = isset($edge->node->entity->id) ? str_replace('kw', '', $edge->node->entity->id) : null;
            $keywordText = isset($edge->node->entity->text->text) ? $edge->node->entity->text->text : null;
            $results[$keywordText] = array(
                'keywordId' => $keywordId,
                'totalTitles' => isset($edge->node->entity->titles->total) ? $edge->node->entity->titles->total : null
            );
        }
        return $results;
    }

}
