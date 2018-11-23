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
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class MemcachedTagAwareAdapter extends TagAwareAdapter
{
    public function __construct($servers, $namespace, $lifetime)
    {
        if (!isset($servers['servers'])) {
            throw new InvalidArgumentException('Invalid memcached configuration. No servers specified.');
        }

        $options = array_key_exists('options', $servers) ? $servers['options'] : [];

        $client = MemcachedAdapter::createConnection($servers['servers'], $options);

        parent::__construct(
            new MemcachedAdapter($client, $namespace, $lifetime),
            new MemcachedAdapter($client, $namespace, $lifetime)
        );
    }
}
