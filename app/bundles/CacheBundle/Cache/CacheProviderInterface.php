<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Cache;

use Symfony\Component\Cache\Simple\Psr6Cache;

interface CacheProviderInterface
{
    public function getSimpleCache(): Psr6Cache;
}