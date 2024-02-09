<?php
#############################################################################
# PHP MovieAPI                                          (c) Itzchak Rehberg #
# written by Itzchak Rehberg <izzysoft AT qumran DOT org>                   #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;

/**
 * Accessing Movie information
 * @author Georgos Giagas
 * @author Ed
 * @author Izzy (izzysoft AT qumran DOT org)
 * @copyright (c) 2002-2004 by Giorgos Giagas and (c) 2004-2009 by Itzchak Rehberg and IzzySoft
 */
class MdbBase extends Config
{
    public $version = '1.3.2';

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var GraphQL
     */
    protected $graphql;

    /**
     * @var string 7 or 8 digit identifier for this person or title
     */
    protected $imdbID;

    /**
     * @param Config $config OPTIONAL override default config
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(Config $config = null, CacheInterface $cache = null)
    {
        $this->config = $config ?: $this;        
        $this->cache = empty($cache) ? new Cache($this->config) : $cache;
        $this->graphql = new GraphQL($this->cache, $this->config);
    }

    /**
     * Retrieve the IMDB ID
     * @return string id IMDBID currently used
     */
    public function imdbid()
    {
        return $this->imdbID;
    }

    /**
     * Set and validate the IMDb ID
     * @param string id IMDb ID
     */
    protected function setid($id)
    {
        if (is_numeric($id)) {
            $this->imdbID = str_pad($id, 7, '0', STR_PAD_LEFT);
        } elseif (preg_match("/(?:nm|tt)(\d{7,8})/", $id, $matches)) {
            $this->imdbID = $matches[1];
        }
    }
}
