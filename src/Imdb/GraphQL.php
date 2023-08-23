<?php
#############################################################################
# GraphQL class                                           (c) tboothman     #
# written by Tom Boothman                                                   #
# Adjusted by Ed                                                            #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

class GraphQL
{

    /**
     * @var Config
     */
    private $config;

    /**
     * GraphQL constructor.
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

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
        $request = new Request('https://api.graphql.imdb.com/', $this->config);
        $request->addHeaderLine("Content-Type", "application/json");

        $payload = json_encode(
            array(
            'operationName' => $queryName,
            'query' => $query,
            'variables' => $variables)
        );

        // @TODO Try use config settings for language etc?
        // graphql docs say 'Affected by headers x-imdb-detected-country, x-imdb-user-country, x-imdb-user-language'
        // x-imdb-user-country: DE changes title {titleText{text}}, but x-imdb-user-language: de does not
        $request->post($payload);

        if (200 == $request->getStatus()) {
            return json_decode($request->getResponseBody())->data;
        } else {
            return new \StdClass();
        }
    }
}
