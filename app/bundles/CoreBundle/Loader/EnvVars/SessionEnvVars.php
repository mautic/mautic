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

class SessionEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        // Set the session name
        $localConfigFile = $defaultConfig->get('local_config_path', uniqid());
        $secretKey       = $config->get('secret_key');

        $key         = $secretKey ? $secretKey : 'mautic';
        $sessionName = md5(md5($localConfigFile).$key);
        $envVars->set('MAUTIC_SESSION_NAME', $sessionName);
    }
}
