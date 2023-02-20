<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic. All rights reserved
 *
 * @link        https://mautic.org
 * @created     12.9.18
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Cache\Adapter;

use Mautic\CacheBundle\Exceptions\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter as TagAwareAdapter;

class RedisTagAwareAdapter extends TagAwareAdapter
{
    public function __construct(array $servers, string $namespace, int $lifetime)
    {
        if (!isset($servers['dsn'])) {
            throw new InvalidArgumentException('Invalid redis configuration. No server specified.');
        }

        $options = array_key_exists('options', $servers) ? $servers['options'] : [];

        $client  = RedisAdapter::createConnection($servers['dsn'], $options);

        parent::__construct($client, $namespace, $lifetime);
    }
}
