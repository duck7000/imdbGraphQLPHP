<?php

#############################################################################
# IMDBPHP6                             (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# written extended & maintained by Ed                                       #
# http://www.izzysoft.de/                                                   #
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
     * @return Title[] array of Titles
     */
    public function search($searchTerms)
    {
        $results = array();
        $query = <<<EOF
query Search {
  mainSearch(first: 10, options: {searchTerm: "$searchTerms", type: TITLE, includeAdult: true}) {
    edges {
      node {
        entity {
          ... on Title {
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
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "Search");
        foreach ($data->mainSearch->edges as $key => $edge) {
            $imdbId = isset($edge->node->entity->id) ? str_replace('tt', '', $edge->node->entity->id) : '';
            $title = isset($edge->node->entity->titleText->text) ? $edge->node->entity->titleText->text : '';
            $movietype = isset($edge->node->entity->titleType->text) ? $edge->node->entity->titleType->text : '';
            $yearRange = '';
            if (isset($edge->node->entity->releaseYear->year)) {
                $yearRange .= $edge->node->entity->releaseYear->year;
                if (isset($edge->node->entity->releaseYear->endYear)) {
                    $yearRange .= '-' . $edge->node->entity->releaseYear->endYear;
                }
            }
            $results[] = array(
                'imdbid' => $imdbId,
                'title' => $title,
                'year' => $yearRange,
                'movietype' => $movietype
            );
        }
        return $results;
    }
}