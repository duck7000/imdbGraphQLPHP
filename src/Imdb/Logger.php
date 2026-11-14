<?php

#############################################################################
# imdbGraphQLPHP                                ed (github user: duck7000)  #
# written by ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Debug logging. Echos html to the page
 * Only used when `\Imdb\Config::debug` is true
 */
class Logger implements LoggerInterface
{
    protected $enabled;

    public function __construct($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * System is unusable
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately
     * Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Critical conditions
     * @note Example: Application component unavailable, unexpected exception
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically be logged and monitored
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors
     * @note Example: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Normal but significant events
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Interesting events
     * @note Example: User logs in, SQL logs
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Detailed debug information
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Logs with an arbitrary level
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log(string $level, Stringable|string $message, array $context = []): void
    {
        if ($this->enabled) {
            $replace = array();
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = "<pre>" . print_r($val, true) . "</pre>";
            }
            $message = strtr($message, $replace);
            switch ($level) {
                case 'emergency':
                case 'alert':
                case 'critical':
                case 'error':
                case 'warning':
                    $color = '#ff0000';
                    break;
                default:
                    $color = '';
                    break;
            }
            echo "<b><font color='$color'>[$level] $message</font></b><br>\n";
        }
    }

}
