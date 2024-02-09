<?php

#############################################################################
# PHP GraphQL API                                             (c) Tboothman #
# written by Tom Boothman                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;

/**
 * Accessing Movie information through GraphQL
 * @author Tom Boothman
 * @author Ed (duck7000)
 * @copyright (c) 2002-2023 by Tom Boothman
 */
class GraphQL
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Config
     */
    private $config;

    /**
     * GraphQL constructor.
     * @param CacheInterface $cache
     * @param Config $config
     */
    public function __construct($cache, $config)
    {
        $this->cache = $cache;
        $this->config = $config;
    }

    public function query($query, $qn = null, $variables = array())
    {
        $key = "gql.$qn." . ($variables ? json_encode($variables) : '') . md5($query) . ".json";
        $fromCache = $this->cache->get($key);

        if ($fromCache != null) {
            return json_decode($fromCache);
        }

        $result = $this->doRequest($query, $qn, $variables);

        $this->cache->set($key, json_encode($result));

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

        if ($this->config->useLocalization === true) {
            if (!empty($this->config->country)) {
                $request->addHeaderLine("X-Imdb-User-Country", $this->config->country);
            }
            if (!empty($this->config->language)) {
                $request->addHeaderLine("X-Imdb-User-Language", $this->config->language);
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
