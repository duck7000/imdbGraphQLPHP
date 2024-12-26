<?php

#############################################################################
# imdbGraphQLPHP                             (c) Ed                         #
# written by ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Search for names on IMDb
 */
class NameSearch extends MdbBase

{
    /**
     * Search IMDb for names matching $searchTerms
     * @param string $searchTerms
     * @return array of names
     */
    public function search($searchTerms)
    {
        $amount = $this->config->nameSearchAmount;
        $results = array();
        $query = <<<EOF
query Search {
  mainSearch(
    first: $amount
    options:{
      searchTerm: "$searchTerms"
      type: NAME
      includeAdult: true
      }
    ) {
    edges {
      node {
        entity {
          ... on Name {
            id
            nameText {
              text
            }
            knownFor(first: 1) {
              edges {
                node{
                  credit {
                    title {
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
            primaryProfessions(limit: 1) {
              category {
                text
              }
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
            $creditKnownFor = array(
                'title' => isset($edge->node->entity->knownFor->edges[0]->node->credit->title->titleText->text) ?
                                 $edge->node->entity->knownFor->edges[0]->node->credit->title->titleText->text : null,
                'titleYear' => isset($edge->node->entity->knownFor->edges[0]->node->credit->title->releaseYear->year) ?
                                     $edge->node->entity->knownFor->edges[0]->node->credit->title->releaseYear->year : null
            );
            $id = isset($edge->node->entity->id) ?
                        str_replace('nm', '', $edge->node->entity->id) : null;
            $name = isset($edge->node->entity->nameText->text) ?
                          $edge->node->entity->nameText->text : null;
            $primaryProfession = isset($edge->node->entity->primaryProfessions[0]->category->text) ?
                                       $edge->node->entity->primaryProfessions[0]->category->text : null;
            // return search results as Name object
            $return = Name::fromSearchResult(
                $id,
                $name,
                $this->config,
                $this->logger,
                $this->cache
            );
            $results[] = array(
                'id' => $id,
                'name' => $name,
                'knownFor' => $creditKnownFor,
                'primaryProfession' => $primaryProfession,
                'nameSearchObject' => $return
            );
        }
        return $results;
    }
}
