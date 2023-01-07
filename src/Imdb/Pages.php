<?php

namespace Imdb;

/**
 * Handles requesting urls, including the caching layer
 */
class Pages
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $pages = array();
    protected $name;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Retrieve the content of the specified $url
     * Caching will be used where possible
     * @return string
     */
    public function get($url)
    {
        if (!empty($this->pages[$url])) {
            return $this->pages[$url];
        }

        if ($this->pages[$url] = $this->requestPage($url)) {
            return $this->pages[$url];
        } else {
            // failed to get page
            return '';
        }
    }

    /**
     * Request the page from IMDb
     * @param $url
     * @return string Page html. Empty string on failure
     * @throws Exception\Http
     */
    protected function requestPage($url)
    {
        $req = $this->buildRequest($url);
        if (!$req->sendRequest()) {
            return '';
        }

        if (200 == $req->getStatus()) {
            return $req->getResponseBody();
        } elseif ($redirectUrl = $req->getRedirect()) {
            return $this->requestPage($redirectUrl);
        } else {
            return '';
        }
    }

    protected function buildRequest($url)
    {
        return new Request($url, $this->config);
    }
}
