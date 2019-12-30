<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Loader\EnvVars;

use Symfony\Component\HttpFoundation\ParameterBag;

interface EnvVarsInterface
{
    /**
     * @param ParameterBag $config  Bag with Mautic local parameters
     * @param ParameterBag $envVars Set environment variables into this bag
     */
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void;
}
