<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Cache\Adapter;

use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class FilesystemTagAwareAdapter extends TagAwareAdapter
{
    public function __construct(?string $prefix, int $lifetime = 0)
    {
        $prefix = 'app_cache_'.$prefix;

        parent::__construct(
            new \Symfony\Component\Cache\Adapter\FilesystemAdapter($prefix, $lifetime),
            new \Symfony\Component\Cache\Adapter\FilesystemAdapter($prefix.'_tags')
        );
    }
}
