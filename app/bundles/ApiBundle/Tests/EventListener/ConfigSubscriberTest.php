<?php

namespace Mautic\ApiBundle\Tests\EventListener;

use Mautic\ApiBundle\EventListener\ConfigSubscriber;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\Helper\Filesystem;
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

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::never())
            ->method('remove');

        $subscriber  = new ConfigSubscriber($filesystem, '/tmp');
        $configEvent = new ConfigEvent($config, new ParameterBag());

        $subscriber->onConfigPreSave($configEvent);

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

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::never())
            ->method('remove');

        $subscriber  = new ConfigSubscriber($filesystem, '/tmp');
        $configEvent = new ConfigEvent($config, new ParameterBag());

        $subscriber->onConfigPreSave($configEvent);

        $this->assertEquals($fixedConfig, $configEvent->getConfig('apiconfig'));
    }

    public function testApiNotEnabledDoesNotClearCache(): void
    {
        $config = [
            'apiconfig' => [
                'api_enabled' => 0,
            ],
        ];

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::never())
            ->method('remove');

        $subscriber  = new ConfigSubscriber($filesystem, '/tmp');
        $configEvent = new ConfigEvent($config, new ParameterBag());

        $subscriber->onConfigPostSave($configEvent);
    }

    public function testApiEnabledButWasEnabledDoesNotClearCache(): void
    {
        $config = [
            'apiconfig' => [
                'api_enabled' => 1,
            ],
        ];
        $originalConfig = [
            'apiconfig' => [
                'parameters' => [
                    'api_enabled' => 1,
                ],
            ],
        ];

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::never())
            ->method('remove');

        $subscriber  = new ConfigSubscriber($filesystem, '/tmp');
        $configEvent = new ConfigEvent($config, new ParameterBag());
        $configEvent->setOriginalNormData($originalConfig);

        $subscriber->onConfigPostSave($configEvent);
    }

    public function testApiEnabledDoesClearCache(): void
    {
        $cacheDir = '/tmp';
        $config   = [
            'apiconfig' => [
                'api_enabled' => 1,
            ],
        ];
        $originalConfig = [
            'apiconfig' => [
                'parameters' => [
                    'api_enabled' => 0,
                ],
            ],
        ];

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(2))
            ->method('exists')
            ->withConsecutive([$cacheDir.'/UrlGenerator.php'], [$cacheDir.'/UrlMatcher.php'])
            ->willReturnOnConsecutiveCalls(false, true);
        $filesystem->expects(self::once())
            ->method('remove')
            ->with($cacheDir.'/UrlMatcher.php');

        $subscriber  = new ConfigSubscriber($filesystem, $cacheDir);
        $configEvent = new ConfigEvent($config, new ParameterBag());
        $configEvent->setOriginalNormData($originalConfig);

        $subscriber->onConfigPostSave($configEvent);
    }
}
