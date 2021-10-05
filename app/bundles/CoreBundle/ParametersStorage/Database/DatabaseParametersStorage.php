<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\ParametersStorage\Database;

use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\ParametersStorage\ParametersStorage;
use Mautic\CoreBundle\ParametersStorage\ParametersStorageInterface;
use Symfony\Component\Cache\Psr16Cache;

class DatabaseParametersStorage implements ParametersStorageInterface
{
    private Psr16Cache $simpleCache;

    public function __construct(CacheProvider $cacheProvider)
    {
        $this->simpleCache = $cacheProvider->getSimpleCache();
    }

    public function isValid(): bool
    {
        return true;
    }

    public function read(): array
    {
        return $this->simpleCache->get(ParametersStorage::PARAMETERS_STORAGE, []);
    }

    public function write(array $parameters): void
    {
        $this->simpleCache->set(ParametersStorage::PARAMETERS_STORAGE, $parameters);
    }
}
