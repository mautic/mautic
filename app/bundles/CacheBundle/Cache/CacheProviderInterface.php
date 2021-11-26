<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Cache;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Simple\Psr6Cache;

interface CacheProviderInterface extends TagAwareAdapterInterface
{
    /**
     * @return Psr6Cache
     */
    public function getSimpleCache();
}
