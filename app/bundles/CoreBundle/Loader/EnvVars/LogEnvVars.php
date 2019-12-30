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

class LogEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        $debugMode = (bool) $config->get('debug');
        $envVars->set('MAUTIC_LOG_MAIN_FORMATTER', $debugMode ? 'mautic.monolog.fulltrace.formatter' : null);
        $envVars->set('MAUTIC_LOG_MAIN_ACTION_LEVEL', $debugMode ? 'debug' : 'error');
        $envVars->set('MAUTIC_LOG_NESTED_ACTION_LEVEL', $debugMode ? 'debug' : 'error');
        $envVars->set('MAUTIC_LOG_MAUTIC_FORMATTER', $debugMode ? 'mautic.monolog.fulltrace.formatter' : null);
        $envVars->set('MAUTIC_LOG_MAUTIC_ACTION_LEVEL', $debugMode ? 'debug' : 'notice');
    }
}
