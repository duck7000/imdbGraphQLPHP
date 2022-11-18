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

/**
 * Accessing Movie information
 * @author Georgos Giagas
 * @author Izzy (izzysoft AT qumran DOT org)
 * @copyright (c) 2002-2004 by Giorgos Giagas and (c) 2004-2009 by Itzchak Rehberg and IzzySoft
 */
class MdbBase extends Config
{
    public $version = '8.0.0';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Pages
     */
    protected $pages;

    protected $page = array();

    protected $xpathPage = array();

    /**
     * @var string 7 digit identifier for this person
     */
    protected $imdbID;

    /**
     * @param Config $config OPTIONAL override default config
     */
    public function __construct(Config $config = null)
    {
        parent::__construct();

        if ($config) {
            foreach (array(
                       "language",
                       "imdbsite",
                       "photodir",
                       "photoroot",
                       "imdb_img_url",
                       "debug",
                       "use_proxy",
                       "ip_address",
                       "proxy_host",
                       "proxy_port",
                       "proxy_user",
                       "proxy_pw",
                       "default_agent",
                       "force_agent"
                     ) as $key) {
                $this->$key = $config->$key;
            }
        }

        $this->config = $config ?: $this;
        $this->pages = new Pages($this->config);
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

    /**
     * Get a page from IMDb, which will be cached in memory for repeated use
     * @param string $context Name of the page or some other context to build the URL with to retrieve the page
     * @return string
     */
    protected function getPage($context = null)
    {
        return $this->pages->get($this->buildUrl($context));
    }

    /**
     * @param string $page
     * @return \DomXPath
     */
    protected function getXpathPage($page)
    {
        if (!empty($this->xpathPage[$page])) {
            return $this->xpathPage[$page];
        }
        $source = $this->getPage($page);
        libxml_use_internal_errors(true);
        /* Creates a new DomDocument object */
        $dom = new \DomDocument;
        /* Load the HTML */
        $dom->loadHTML('<?xml encoding="utf-8" ?>' .$source);
        /* Create a new XPath object */
        $this->xpathPage[$page] = new \DomXPath($dom);
        return $this->xpathPage[$page];
    }

    /**
     * Overrideable method to build the URL used by getPage
     * @param string $context OPTIONAL
     * @return string
     */
    protected function buildUrl($context = null)
    {
        return '';
    }
}
