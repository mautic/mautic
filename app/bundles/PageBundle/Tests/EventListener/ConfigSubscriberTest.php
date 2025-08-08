<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\PageBundle\EventListener\ConfigSubscriber;
use Mautic\PageBundle\Form\Type\ConfigTrackingPageType;
use Mautic\PageBundle\Form\Type\ConfigType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigSubscriberTest extends TestCase
{
    private ConfigSubscriber $configSubscriber;

    /**
     * @var ConfigBuilderEvent&MockObject
     */
    private MockObject $configBuilderEvent;

    /**
     * @var ConfigEvent&MockObject
     */
    private MockObject $configEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configSubscriber    = new ConfigSubscriber();
        $this->configBuilderEvent  = $this->createMock(ConfigBuilderEvent::class);
        $this->configEvent         = $this->createMock(ConfigEvent::class);
    }

    public function testSubscribedEvents(): void
    {
        $subscribedEvents = ConfigSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(ConfigEvents::CONFIG_ON_GENERATE, $subscribedEvents);
        $this->assertArrayHasKey(ConfigEvents::CONFIG_PRE_SAVE, $subscribedEvents);
    }

    public function testOnConfigGenerate(): void
    {
        $expectedConfig = [
            'bundle'     => 'PageBundle',
            'formAlias'  => 'pageconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticPage/FormTheme/Config/_config_pageconfig_widget.html.twig',
            'parameters' => [
                'cat_in_page_url'  => false,
                'google_analytics' => false,
                'footer_script'    => false,
            ],
        ];

        $this->configBuilderEvent
            ->expects($this->once())
            ->method('addForm')
            ->with($expectedConfig);

        $this->configSubscriber->onConfigGenerate($this->configBuilderEvent);
    }

    public function testOnConfigGenerateTracking(): void
    {
        $expectedConfig = [
            'bundle'     => 'PageBundle',
            'formAlias'  => 'trackingconfig',
            'formType'   => ConfigTrackingPageType::class,
            'formTheme'  => '@MauticPage/FormTheme/Config/_config_trackingconfig_widget.html.twig',
            'parameters' => [
                'anonymize_ip'                          => false,
                'track_contact_by_ip'                   => false,
                'facebook_pixel_id'                     => null,
                'facebook_pixel_trackingpage_enabled'   => false,
                'facebook_pixel_landingpage_enabled'    => false,
                'google_analytics_id'                   => null,
                'google_analytics_trackingpage_enabled' => false,
                'google_analytics_landingpage_enabled'  => false,
                'google_analytics_anonymize_ip'         => false,
                'do_not_track_404_anonymous'            => false,
            ],
        ];

        $this->configBuilderEvent
            ->expects($this->once())
            ->method('addForm')
            ->with($expectedConfig);

        $this->configSubscriber->onConfigGenerateTracking($this->configBuilderEvent);
    }

    public function testOnConfigSaveWithGoogleAnalytics(): void
    {
        $values = [
            'pageconfig' => [
                'google_analytics' => '<script>console.log("test");</script>',
            ],
        ];

        $expectedValues = [
            'pageconfig' => [
                'google_analytics' => htmlspecialchars('<script>console.log("test");</script>'),
            ],
        ];

        $this->configEvent
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($values);

        $this->configEvent
            ->expects($this->once())
            ->method('setConfig')
            ->with($expectedValues);

        $this->configSubscriber->onConfigSave($this->configEvent);
    }

    public function testOnConfigSaveWithFooterScript(): void
    {
        $values = [
            'pageconfig' => [
                'footer_script' => '<script>console.log("footer");</script>',
            ],
        ];

        $expectedValues = [
            'pageconfig' => [
                'footer_script' => htmlspecialchars('<script>console.log("footer");</script>'),
            ],
        ];

        $this->configEvent
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($values);

        $this->configEvent
            ->expects($this->once())
            ->method('setConfig')
            ->with($expectedValues);

        $this->configSubscriber->onConfigSave($this->configEvent);
    }

    public function testOnConfigSaveWithBothScripts(): void
    {
        $values = [
            'pageconfig' => [
                'google_analytics' => '<script>console.log("head");</script>',
                'footer_script'    => '<script>console.log("footer");</script>',
            ],
        ];

        $expectedValues = [
            'pageconfig' => [
                'google_analytics' => htmlspecialchars('<script>console.log("head");</script>'),
                'footer_script'    => htmlspecialchars('<script>console.log("footer");</script>'),
            ],
        ];

        $this->configEvent
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($values);

        $this->configEvent
            ->expects($this->exactly(2))
            ->method('setConfig');

        $this->configSubscriber->onConfigSave($this->configEvent);
    }

    public function testOnConfigSaveWithEmptyValues(): void
    {
        $values = [
            'pageconfig' => [
                'google_analytics' => '',
                'footer_script'    => '',
            ],
        ];

        $this->configEvent
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($values);

        $this->configEvent
            ->expects($this->never())
            ->method('setConfig');

        $this->configSubscriber->onConfigSave($this->configEvent);
    }
}
