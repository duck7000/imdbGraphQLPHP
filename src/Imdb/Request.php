<?php

declare(strict_types=1);

#############################################################################
# IMDBPHP                              (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * The request class
 * Here we emulate a browser accessing the IMDB site. You don't need to
 * call any of its method directly - they are rather used by the IMDB classes
 */
class Request
{
    private $ch;
    private $page;
    private array $requestHeaders = array();
    private array $responseHeaders = array();
    private object $config;

    /**
     * No need to call this.
     * @param string $url URL to fetch data
     * @param object Config $config OPTIONAL override default config
     */
    public function __construct($url, Config $config)
    {
        $this->config = $config;
        $this->ch = curl_init($url);
        curl_setopt($this->ch, CURLOPT_ACCEPT_ENCODING, "");
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array(&$this, "callbackCurloptHeaderFunction"));
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0');
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->config->curloptTimeout);
    }

    /**
     * Add header line to requestHeaders array
     * @param string $name
     * @param string $value
     * @return void
     */
    public function addHeaderLine(string $name, string $value): void
    {
        $this->requestHeaders[] = "$name: $value";
    }

    /**
     * Send a POST request
     * @param string $content
     * @return bool
     */
    public function post(string $content): bool
    {
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $content);
        return $this->sendRequest();
    }

    /**
     * Send a request to the movie site
     * @return bool
     */
    public function sendRequest(): bool
    {
        $this->responseHeaders = array();
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->requestHeaders);
        $this->page = curl_exec($this->ch);
        if ($this->page !== false) {
            return true;
        }
        return false;
    }

    /**
     * Get the Response body
     * @return array
     */
    public function getResponseBody(): string
    {
        return $this->page;
    }

    /**
     * Get a header value from the response
     * @param string $header header field name
     * @return string
     */
    public function getResponseHeader(string $header): string
    {
        $headers = $this->getLastResponseHeaders();
        foreach ($headers as $head) {
            if (is_integer(stripos($head, $header))) {
                $hstart = strpos($head, ": ");
                $head = trim(substr($head, $hstart + 2, 100));
                return $head;
            }
        }
        return '';
    }

    /**
     * HTTP status code of the last response
     * @return int|null
     */
    public function getStatus(): ?int
    {
        $headers = $this->getLastResponseHeaders();
        if (empty($headers[0])) {
            return null;
        }
        if (!preg_match("#^HTTP/[\d\.]+ (\d+)#i", $headers[0], $matches)) {
            return null;
        }
        return (int)$matches[1];
    }

    /**
     * Get last response headers
     * @return array
     */
    public function getLastResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * Callback curl_opt header function
     * @param object $ch curlHandle
     * @param string $str
     * @return int
     */
    private function callbackCurloptHeaderFunction($ch, string $str): int
    {
        $len = strlen($str);
        if ($len) {
            $this->responseHeaders[] = $str;
        }
        return $len;
    }

}
