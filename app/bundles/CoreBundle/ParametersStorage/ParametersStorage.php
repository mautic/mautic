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

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class ParametersStorage
{
    const STORAGE_DEFAULT = 'parameters_storage';

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @var ParametersStorageInterface[]
     */
    private $storages = [];

    public function addStorage(string $id, ParametersStorageInterface $storage)
    {
        $this->storages[$id] = $storage;
    }

    public function getStorage(string $name = null)
    {
        if (!$name) {
            $name = $this->coreParametersHelper->get(self::STORAGE_DEFAULT);
        }
        if (isset($this->storages[$name])) {
            return $this->storages[$name];
        }

        throw new \InvalidArgumentException(sprintf('There is not a parameters storage  %s', $name));
    }
}
