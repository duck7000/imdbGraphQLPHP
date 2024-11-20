<?php

#############################################################################
# PHP GraphQL API                                             (c) Tboothman #
# written by Tom Boothman                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * GraphQL constructor.
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct($cache, $logger, $config)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function query($query, $qn = null, $variables = array())
    {
        $key = "gql.$qn." . ($variables ? json_encode($variables) : '') . md5($query) . ".json";
        $fromCache = $this->cache->get($key);
        if ($fromCache != null) {
            return json_decode($fromCache);
        }
        // strip spaces from query due to hosters request limit
        $fullQuery = implode("\n", array_map('trim', explode("\n", $query)));
        $result = $this->doRequest($fullQuery, $qn, $variables);
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
        $request = new Request('https://api.graphql.imdb.com/', $this->config);
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
                'variables' => $variables
            )
        );
        $this->logger->info("[GraphQL] Requesting $queryName");
        $request->post($payload);
        if (200 == $request->getStatus()) {
            return json_decode($request->getResponseBody())->data;
        } else {
            $this->logger->error(
                "[GraphQL] Failed to retrieve query [{queryName}]. Response headers:{headers}. Response body:{body}",
                array('queryName' => $queryName, 'headers' => $request->getLastResponseHeaders(), 'body' => $request->getResponseBody())
            );
            $errorId = 'Not Used'; // Some classes don't use imdbId like Chart, Trailers, Calendar and KeywordSearch
            if (!empty($variables['id'])) {
                $errorId = $variables['id'];
            }
            throw new \Exception("Failed to retrieve query [$queryName] , IMDb id [$errorId]");
        }
    }
}
