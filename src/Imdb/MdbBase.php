<?php

#############################################################################
# imdbGraphQLPHP                                 ed (github user: duck7000) #
# written by ed (github user: duck7000)                                     #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Accessing Movie information
 * @author Georgos Giagas
 * @author Ed
 * @author Izzy (izzysoft AT qumran DOT org)
 */
class MdbBase extends Config
{
    public $version = '3.2.0';

    /**
     * @var object CacheInterface
     */
    protected $cache;

    /**
     * @var object LoggerInterface
     */
    protected $logger;

    /**
     * @var object Config
     */
    protected $config;

    /**
     * @var object GraphQL
     */
    protected $graphql;

    /**
     * @var string 7 or 8 digit identifier for this person or title
     */
    protected $imdbID;

    /**
     * @param object Config $config OPTIONAL override default config
     * @param object LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param object CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        $this->config = $config ?: $this;
        $this->logger = empty($logger) ? new Logger($this->debug) : $logger;
        $this->cache = empty($cache) ? new Cache($this->config, $this->logger) : $cache;
        $this->graphql = new GraphQL($this->cache, $this->logger, $this->config);
    }

    /**
     * Retrieve the IMDB ID
     * @return string
     */
    public function imdbid()
    {
        return $this->imdbID;
    }

    /**
     * Set and validate the IMDb ID
     * @param string
     */
    protected function setid($id)
    {
        if (is_numeric($id)) {
            $this->imdbID = str_pad($id, 7, '0', STR_PAD_LEFT);
        } elseif (preg_match("/(?:nm|tt|ur)(\d{7,10})/", $id, $matches)) {
            $this->imdbID = $matches[1];
        } else {
            $this->debugScalar("<BR>setid: Invalid IMDB ID '$id'!<BR>");
        }
    }

    #---------------------------------------------------------[ Debug helpers ]---
    protected function debugScalar($scalar)
    {
        $this->logger->error($scalar);
    }

    protected function debugObject($object)
    {
        $this->logger->error('{object}', array('object' => $object));
    }

    protected function debugHtml($html)
    {
        $this->logger->error(htmlentities($html));
    }

}
