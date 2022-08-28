<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\LeadBundle\EventListener\ConfigSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigSubscriberTest extends TestCase
{
    private ConfigSubscriber $configSubscriber;

    /**
     * @var ConfigBuilderEvent&MockObject
     */
    private $configBuilderEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configSubscriber   = new ConfigSubscriber();
        $this->configBuilderEvent = $this->createMock(ConfigBuilderEvent::class);
    }

    public function testSubscribedEvents(): void
    {
        $subscribedEvents = ConfigSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(ConfigEvents::CONFIG_ON_GENERATE, $subscribedEvents);
    }

    public function testThatWeAreAddingFormsToTheConfig(): void
    {
        $leadConfig = [
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'leadconfig',
            'formType'   => 'Mautic\\LeadBundle\\Form\\Type\\ConfigType',
            'formTheme'  => 'MauticLeadBundle:FormTheme\\Config',
            'parameters' => null,
        ];

        $segmentConfig = [
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'segment_config',
            'formType'   => 'Mautic\\LeadBundle\\Form\\Type\\SegmentConfigType',
            'formTheme'  => 'MauticLeadBundle:FormTheme\\Config',
            'parameters' => null,
        ];

        $this->configBuilderEvent
            ->expects($this->exactly(2))
            ->method('addForm')
            ->withConsecutive([$leadConfig], [$segmentConfig]);

        $this->configSubscriber->onConfigGenerate($this->configBuilderEvent);
    }
}
