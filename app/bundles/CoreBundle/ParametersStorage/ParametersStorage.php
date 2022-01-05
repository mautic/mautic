<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\ParametersStorage;

use Mautic\CoreBundle\Loader\ParameterLoader;

class ParametersStorage
{
    const PARAMETERS_STORAGE = 'parameters_storage';

    private \Symfony\Component\HttpFoundation\ParameterBag $parameters;

    public function __construct()
    {
        // we cannot load CoreParametersHelper, because this service is used in it
        $loader = new ParameterLoader();

        $this->parameters = $loader->getParameterBag();
    }

    /**
     * @var ParametersStorageInterface[]
     */
    private array $storages = [];

    public function addStorage(string $id, ParametersStorageInterface $storage)
    {
        $this->storages[$id] = $storage;
    }

    public function getStorage(string $name = null): ParametersStorageInterface
    {
        if (!$name) {
            $name = $this->parameters->get(self::PARAMETERS_STORAGE);
        }
        if (isset($this->storages[$name])) {
            return $this->storages[$name];
        }

        throw new \InvalidArgumentException(sprintf('There is not a parameters storage  %s', $name));
    }
}
