<?php

namespace Mautic\MessengerBundle\Loader\EnvVars;

use Mautic\CoreBundle\Loader\EnvVars\EnvVarsInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class MessengerEnvLoader implements EnvVarsInterface
{
    /**
     * @param ParameterBag<string,string> $config
     * @param ParameterBag<string,string> $defaultConfig
     * @param ParameterBag<string,string> $envVars
     */
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        $envVars->set('MAUTIC_MESSENGER_TRANSPORT_DSN', $config->get('messenger_dsn'));

        $consumerValue = getenv('MAUTIC_MESSENGER_CONSUMER_NAME');
        $envVars->set('MAUTIC_MESSENGER_CONSUMER_NAME', !empty($consumerValue) ? $consumerValue : 'consumer');
    }
}
