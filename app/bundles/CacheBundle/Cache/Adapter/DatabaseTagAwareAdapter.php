<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Cache\Adapter;

use Doctrine\DBAL\Connection;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class DatabaseTagAwareAdapter extends TagAwareAdapter
{
    public function __construct(Connection $connection, array $servers, ?string $namespace, int $lifetime)
    {
        if (isset($servers['cache_lifetime'])) {
            $lifetime = $servers['cache_lifetime'];
        }

        parent::__construct(
            new \Symfony\Component\Cache\Adapter\PdoAdapter($connection, (string) $namespace, $lifetime),
            new \Symfony\Component\Cache\Adapter\PdoAdapter($connection, (string) $namespace, $lifetime)
        );
    }
}
