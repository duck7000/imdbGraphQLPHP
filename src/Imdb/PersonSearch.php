<?php

namespace Imdb;


/**
 * Search for people on IMDb
 * @author Izzy (izzysoft AT qumran DOT org)
 * @copyright 2008-2009 by Itzchak Rehberg and IzzySoft
 */
class PersonSearch extends MdbBase

{
    /**
     * Search IMDb for titles matching $searchTerms
     * @param string $searchTerms
     * @return Title[] array of Titles
     */
    public function search($searchTerms)
    {
        $results = array();
        $query = <<<EOF
query Search {
  mainSearch(first: 10, options: {searchTerm: "$searchTerms", type: NAME, includeAdult: true}) {
    edges {
      node {
        entity {
          ... on Name {
            id
            nameText {
              text
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
            $nameId = isset($edge->node->entity->id) ? str_replace('nm', '', $edge->node->entity->id) : '';
            $name = isset($edge->node->entity->nameText->text) ? $edge->node->entity->nameText->text : '';
            $results[] = array(
                'id' => $nameId,
                'name' => $name
            );
        }
        return $results;
    }
}
