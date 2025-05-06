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
    private MockObject $configBuilderEvent;

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
            'formType'   => \Mautic\LeadBundle\Form\Type\ConfigType::class,
            'formTheme'  => '@MauticLead/FormTheme/Config/_config_companyconfig_widget.html.twig',
            'parameters' => null,
        ];

        $segmentConfig = [
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'segment_config',
            'formType'   => \Mautic\LeadBundle\Form\Type\SegmentConfigType::class,
            'formTheme'  => '@MauticLead/FormTheme/Config/_config_leadconfig_widget.html.twig',
            'parameters' => null,
        ];
        $matcher = $this->exactly(2);

        $this->configBuilderEvent
            ->expects($matcher)
            ->method('addForm')->willReturnCallback(function (...$parameters) use ($matcher, $leadConfig, $segmentConfig) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($leadConfig, $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($segmentConfig, $parameters[0]);
                }
            });

        $this->configSubscriber->onConfigGenerate($this->configBuilderEvent);
    }
}
