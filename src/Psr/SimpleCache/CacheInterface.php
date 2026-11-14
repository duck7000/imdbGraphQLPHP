<?php

declare(strict_types=1);

#############################################################################
# imdbGraphQLPHP Trailers                https://www.imdb.com/trailers/     #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Psr\SimpleCache;

interface CacheInterface
{
    /**
     * Fetches a value from the cache
     * @param string $key     The unique key of this item in the cache
     * @param string|null  $default Default value to return if the key does not exist
     * @return string|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value
     */
    public function get(string $key, ?string $default = null): ?string;

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time
     * @param string $key   The key of the item to store
     * @param string $value The value of the item to store, must be serializable
     * @param null|int|DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                     the driver supports TTL then the library may set a default value
     *                                     for it or let the driver take care of that
     * @return bool
     */
    public function set(string $key, string $value, ?int $ttl = null): bool;

}
