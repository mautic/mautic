<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc. Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 * @created     12.9.18
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Cache\Adapter;

use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class FilesystemTagAwareAdapter extends TagAwareAdapter
{
    public function __construct($prefix, $lifetime)
    {
        $prefix = 'app_cache_'.$prefix;

        parent::__construct(
            new \Symfony\Component\Cache\Adapter\FilesystemAdapter($prefix, $lifetime),
            new \Symfony\Component\Cache\Adapter\FilesystemAdapter($prefix.'_tags')
        );
    }
}
