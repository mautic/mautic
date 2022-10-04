<?php

namespace Mautic\ApiBundle\Tests\EventListener;

use Mautic\ApiBundle\EventListener\ConfigSubscriber;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\Tests\CommonMocks;
use Symfony\Component\HttpFoundation\ParameterBag;

class ConfigSubscriberTest extends CommonMocks
{
    public function testWithUnsetApiBasicAuthSetting()
    {
        /**
         * We need a config array where api_enable_basic_auth is not set
         * (for example, in a hosted environment where customers are not allowed
         * to enable basic auth on the API). Saving the config shouldn't throw
         * any undefined notices/warnings in that case.
         */
        $config = ['apiconfig' => []];

        $subscriber  = new ConfigSubscriber();
        $configEvent = new ConfigEvent($config, new ParameterBag());

        $subscriber->onConfigSave($configEvent);

        $this->assertEquals($config, $configEvent->getConfig());
    }

    public function testWithIntegerApiBasicAuthSetting()
    {
        // Make sure the subscriber converts an integer value to boolean.
        $config = [
            'apiconfig' => [
                'api_enable_basic_auth' => 1,
            ],
        ];

        $fixedConfig = [
            'api_enable_basic_auth' => true,
        ];

        $subscriber  = new ConfigSubscriber();
        $configEvent = new ConfigEvent($config, new ParameterBag());

        $subscriber->onConfigSave($configEvent);

        $this->assertEquals($fixedConfig, $configEvent->getConfig('apiconfig'));
    }
}
