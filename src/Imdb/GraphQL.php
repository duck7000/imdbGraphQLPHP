<?php

#############################################################################
# PHP GraphQL API                                             (c) Tboothman #
# written by Tom Boothman                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Accessing Movie information through GraphQL
 * @author Tom Boothman
 * @copyright (c) 2002-2023 by Tom Boothman
 */
class GraphQL
{

    public function query($query, $qn = null, $variables = array())
    {
        $key = "gql.$qn." . ($variables ? json_encode($variables) : '') . md5($query) . ".json";

        $result = $this->doRequest($query, $qn, $variables);

        return $result;
    }

    /**
     * @param string $query
     * @param string|null $queryName
     * @param array $variables
     * @return \stdClass
     */
    private function doRequest($query, $queryName = null, $variables = array())
    {
        $request = new Request('https://api.graphql.imdb.com/');
        $request->addHeaderLine("Content-Type", "application/json");

        $payload = json_encode(
            array(
            'operationName' => $queryName,
            'query' => $query,
            'variables' => $variables)
        );

        $request->post($payload);

        if (200 == $request->getStatus()) {
            return json_decode($request->getResponseBody())->data;
        } else {
            return new \StdClass();
        }
    }
}
