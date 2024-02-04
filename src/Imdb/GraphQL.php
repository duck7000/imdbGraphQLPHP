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

    /**
     * @var boolean useLocalization set true to use localization
     * leave this to false if you want US American English
     */
    protected $useLocalization = false;

    /**
     * @var string country set country code
     * possible values: 
     * CA (Canada)
     * FR (France)
     * DE (Germany)
     * IN (Indonesia)
     * IT (Italy)
     * BR (Brazil)
     * ES (Spain)
     * MX (Mexico)
     */
    protected $country = "";

    /**
     * @var string language set language code
     * possible values: 
     * fr-CA (French Canada)
     * fr-FR (French France)
     * de-DE (German Germany)
     * hi-IN (hindi)
     * it-IT (Italian Italy)
     * pt-BR (Portugues Brazil)
     * es-ES (Spanisch Spain)
     * es-MX (Spanisch Mexico)
     */
    protected $language = "";

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

        if ($this->useLocalization === true) {
            if (!empty($this->country)) {
                $request->addHeaderLine("X-Imdb-User-Country", $this->country);
            }
            if (!empty($this->language)) {
                $request->addHeaderLine("X-Imdb-User-Language", $this->language);
            }
        }

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
