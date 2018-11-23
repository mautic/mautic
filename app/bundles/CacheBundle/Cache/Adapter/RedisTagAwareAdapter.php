<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache\Adapter;

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc. Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 * @created     12.9.18
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CacheBundle\Exceptions\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class RedisTagAwareAdapter extends TagAwareAdapter
{
    public function __construct($servers, $namespace, $lifetime)
    {
        if (!isset($servers['dsn'])) {
            throw new InvalidArgumentException('Invalid redis configuration. No server specified.');
        }

        $options = array_key_exists('options', $servers) ? $servers['options'] : [];

        $client = RedisAdapter::createConnection($servers['dsn'], $options);

        parent::__construct(
            new RedisAdapter($client, $namespace, $lifetime),
            new RedisAdapter($client, $namespace.'-tags', $lifetime)
        );
    }
}
