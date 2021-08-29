<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\ParametersStorage\Local;

use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\ParametersStorage\ParametersStorageInterface;

class LocalParametersStorage implements ParametersStorageInterface
{
    private Configurator $configurator;

    public function __construct(Configurator $configurator)
    {
        $this->configurator = $configurator;
    }

    public function validation()
    {
    }

    public function read(): array
    {
        return $this->configurator->getParameters();
    }

    public function write(array $parameters): void
    {
        $params = $this->configurator->getParameters();
        if (empty($params['secret_key'])) {
            $this->configurator->mergeParameters(['secret_key' => EncryptionHelper::generateKey()]);
        }
        $this->configurator->mergeParameters($parameters);
        $this->configurator->write();
    }
}
