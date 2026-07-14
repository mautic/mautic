<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\LeadBundle\EventListener\ConfigSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfigSubscriberTest extends TestCase
{
    private ConfigSubscriber $configSubscriber;

    /**
     * @var MockObject&ConfigBuilderEvent
     */
    private MockObject $configBuilderEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configSubscriber   = new ConfigSubscriber();
        $this->configBuilderEvent = $this->createMock(ConfigBuilderEvent::class);
        $leadBundleParameters     = [
            'company_unique_identifiers_operator'   => CompositeExpression::TYPE_OR,
            'company_columns'                       => [
                'companyname',
                'id',
            ],
            'contact_allow_multiple_companies'      => true,
            'segment_rebuild_time_warning'          => 30,
        ];

        $this->configBuilderEvent->method('getParametersFromConfig')
            ->willReturnCallback(fn (string $bundle): array => match ($bundle) {
                'MauticLeadBundle' => $leadBundleParameters,
                default            => [],
            });
    }

    public function testSubscribedEvents(): void
    {
        $subscribedEvents = ConfigSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(ConfigEvents::CONFIG_ON_GENERATE, $subscribedEvents);
        $handlers = ConfigSubscriber::getSubscribedEvents()[ConfigEvents::CONFIG_ON_GENERATE];
        $this->assertCount(2, $handlers);
    }

    public function testLeadAndSegmentFormsUseExpectedThemes(): void
    {
        $invocations = [];
        $this->configBuilderEvent
            ->expects($this->exactly(2))
            ->method('addForm')
            ->willReturnCallback(function (array $form) use (&$invocations): ConfigBuilderEvent {
                $invocations[] = $form;

                return $this->configBuilderEvent;
            });

        $this->configSubscriber->onConfigGenerate($this->configBuilderEvent);

        $this->assertCount(2, $invocations);

        $this->assertSame('leadconfig', $invocations[0]['formAlias']);
        $this->assertSame(\Mautic\LeadBundle\Form\Type\ConfigType::class, $invocations[0]['formType']);
        $this->assertSame('@MauticLead/FormTheme/Config/_config_leadconfig_widget.html.twig', $invocations[0]['formTheme']);

        $this->assertSame('segment_config', $invocations[1]['formAlias']);
        $this->assertSame(\Mautic\LeadBundle\Form\Type\SegmentConfigType::class, $invocations[1]['formType']);
        $this->assertSame('@MauticLead/FormTheme/Config/_config_segment_config_widget.html.twig', $invocations[1]['formTheme']);

        $this->assertArrayNotHasKey('company_columns', $invocations[0]['parameters']);
        $this->assertArrayNotHasKey('company_unique_identifiers_operator', $invocations[0]['parameters']);

        $this->assertArrayNotHasKey('company_columns', $invocations[1]['parameters']);
    }

    public function testCompanyFormPassesColumnConfiguration(): void
    {
        $matcher = $this->once();

        $this->configBuilderEvent
            ->expects($matcher)
            ->method('addForm')
            ->willReturnCallback(function (array $form): ConfigBuilderEvent {
                $this->assertSame('companyconfig', $form['formAlias']);
                $this->assertSame(\Mautic\LeadBundle\Form\Type\ConfigCompanyType::class, $form['formType']);
                $this->assertSame('@MauticLead/FormTheme/Config/_config_companyconfig_widget.html.twig', $form['formTheme']);
                $this->assertArrayHasKey('company_columns', $form['parameters']);
                $this->assertSame([
                    'companyname',
                    'id',
                ], $form['parameters']['company_columns']);

                return $this->configBuilderEvent;
            });

        $this->configSubscriber->onConfigCompanyGenerate($this->configBuilderEvent);
    }
}
