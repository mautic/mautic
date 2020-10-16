<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class IntNullableProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        $env = $getEnv($name);

        return null === $env ? null : (int) $env;
    }

    public static function getProvidedTypes()
    {
        return [
            'intNullable' => 'string|int',
        ];
    }
}
