<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\FormType;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Form\Type\IntegrationCampaignsType;
use Mautic\PluginBundle\Form\Type\IntegrationConfigType;
use Mautic\PluginBundle\Form\Type\IntegrationsListType;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class IntegrationsListTypeTest extends TestCase
{
    public function testDataDoesNotHaveIntegration(): void
    {
        $pluginName = 'plugin name';

        $integration1 = $this->createMock(Integration::class);
        $integration1->expects(self::once())
            ->method('isPublished')
            ->willReturn(false);
        $integration1->expects(self::never())
            ->method('getPlugin');

        $plugin = $this->createMock(Plugin::class);
        $plugin->expects(self::once())
            ->method('getName')
            ->willReturn($pluginName);

        $integration2 = $this->createMock(Integration::class);
        $integration2->expects(self::once())
            ->method('isPublished')
            ->willReturn(true);
        $integration2->expects(self::once())
            ->method('getPlugin')
            ->willReturn($plugin);

        $integrationInstance1 = $this->createMock(AbstractIntegration::class);
        $integrationInstance1->expects(self::once())
            ->method('getIntegrationSettings')
            ->willReturn($integration1);

        $integrationInstance2 = $this->createMock(AbstractIntegration::class);
        $integrationInstance2->expects(self::once())
            ->method('getIntegrationSettings')
            ->willReturn($integration2);
        $integrationInstance2->expects(self::once())
            ->method('getDisplayName')
            ->willReturn('Integration 2');
        $integrationInstance2->expects(self::once())
            ->method('getName')
            ->willReturn('integration-2');

        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $integrationHelper->expects(self::once())
            ->method('getIntegrationObjects')
            ->with(null, 'features', true)
            ->willReturn(['integration1' => $integrationInstance1, 'integration2' => $integrationInstance2]);
        $integrationHelper->method('getIntegrationObject')
            ->willReturn($this->createMock(AbstractIntegration::class));

        $callsForm = 0;
        $form      = $this->createMock(FormInterface::class);
        $form->method('add')
            ->willReturnCallback(static function (string $key, string $fieldFQCN, array $options) use (&$callsForm): void {
                if ('config' === $key) {
                    ++$callsForm;
                    self::assertSame(IntegrationConfigType::class, $fieldFQCN);
                    self::assertArrayHasKey('integration', $options);
                    self::assertNull($options['integration']);
                    self::assertArrayHasKey('data', $options);
                    self::assertSame([], $options['data']);
                }

                if ('campaign_member_status' === $key) {
                    ++$callsForm;
                    self::assertSame(IntegrationCampaignsType::class, $fieldFQCN);
                    self::assertArrayHasKey('attr', $options);
                    self::assertSame('integration-campaigns-status hide', $options['attr']['class']);
                    self::assertArrayHasKey('data', $options);
                    self::assertSame([], $options['data']);
                }
            });

        $data = [];

        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects(self::once())
            ->method('getForm')
            ->willReturn($form);
        $formEvent->expects(self::once())
            ->method('getData')
            ->willReturn($data);

        $callsBuilder = 0;
        $builder      = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')
            ->willReturnCallback(static function (string $key, string $fieldFQCN, array $options) use ($pluginName, &$callsBuilder): void {
                if ('integration' === $key) {
                    ++$callsBuilder;
                    self::assertSame(ChoiceType::class, $fieldFQCN);
                    self::assertArrayHasKey('choices', $options);
                    self::assertSame([
                        ''          => '',
                        $pluginName => [
                            'Integration 2' => 'integration-2',
                        ],
                    ], $options['choices']);
                }
            });

        $calledCallback = false;
        $builder->expects(self::exactly(2))
            ->method('addEventListener')
            ->willReturnCallback(static function (string $eventName, callable $callback) use ($formEvent, &$calledCallback): void {
                self::assertContains($eventName, [FormEvents::PRE_SET_DATA, FormEvents::PRE_SUBMIT]);

                if (!$calledCallback) {
                    $calledCallback = true;
                    $callback($formEvent);
                }
            });

        $integrationsListType = new IntegrationsListType($integrationHelper);
        $integrationsListType->buildForm($builder, ['supported_features' => 'features']);

        self::assertSame(1, $callsBuilder);
        self::assertSame(2, $callsForm);
    }

    public function testDataHaveIntegration(): void
    {
        $pluginName = 'plugin name';

        $integration1 = $this->createMock(Integration::class);
        $integration1->expects(self::once())
            ->method('isPublished')
            ->willReturn(false);
        $integration1->expects(self::never())
            ->method('getPlugin');

        $plugin = $this->createMock(Plugin::class);
        $plugin->expects(self::once())
            ->method('getName')
            ->willReturn($pluginName);

        $integration2 = $this->createMock(Integration::class);
        $integration2->expects(self::once())
            ->method('isPublished')
            ->willReturn(true);
        $integration2->expects(self::once())
            ->method('getPlugin')
            ->willReturn($plugin);

        $integrationInstance1 = $this->createMock(AbstractIntegration::class);
        $integrationInstance1->expects(self::once())
            ->method('getIntegrationSettings')
            ->willReturn($integration1);

        $integrationInstance2 = $this->createMock(AbstractIntegration::class);
        $integrationInstance2->expects(self::once())
            ->method('getIntegrationSettings')
            ->willReturn($integration2);
        $integrationInstance2->expects(self::once())
            ->method('getDisplayName')
            ->willReturn('Integration 2');
        $integrationInstance2->expects(self::once())
            ->method('getName')
            ->willReturn('integration-2');

        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $integrationHelper->expects(self::once())
            ->method('getIntegrationObjects')
            ->with(null, 'features', true)
            ->willReturn(['integration1' => $integrationInstance1, 'integration2' => $integrationInstance2]);
        $integrationHelper->method('getIntegrationObject')
            ->willReturn($this->createMock(AbstractIntegration::class));

        $callsForm = 0;
        $form      = $this->createMock(FormInterface::class);
        $form->method('add')
            ->willReturnCallback(static function (string $key, string $fieldFQCN, array $options) use ($integrationInstance1, &$callsForm): void {
                if ('config' === $key) {
                    ++$callsForm;
                    self::assertSame(IntegrationConfigType::class, $fieldFQCN);
                    self::assertArrayHasKey('integration', $options);
                    self::assertSame($integrationInstance1, $options['integration']);
                    self::assertArrayHasKey('data', $options);
                    self::assertSame(['config' => 'test'], $options['data']);
                }

                if ('campaign_member_status' === $key) {
                    ++$callsForm;
                    self::assertSame(IntegrationCampaignsType::class, $fieldFQCN);
                    self::assertArrayHasKey('attr', $options);
                    self::assertSame('integration-campaigns-status', $options['attr']['class']);
                    self::assertArrayHasKey('data', $options);
                    self::assertSame([
                        'campaign_member_status' => true,
                        'some'                   => 'other',
                    ], $options['data']);
                }
            });

        $data = [
            'integration' => 'integration1',
            'config'      => [
                'config' => 'test',
            ],
            'campaign_member_status' => [
                'campaign_member_status' => true,
                'some'                   => 'other',
            ],
        ];

        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects(self::exactly(2))
            ->method('getForm')
            ->willReturn($form);
        $formEvent->expects(self::exactly(2))
            ->method('getData')
            ->willReturn($data);

        $callsBuilder = 0;
        $builder      = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')
            ->willReturnCallback(static function (string $key, string $fieldFQCN, array $options) use ($pluginName, &$callsBuilder): void {
                if ('integration' === $key) {
                    ++$callsBuilder;
                    self::assertSame(ChoiceType::class, $fieldFQCN);
                    self::assertArrayHasKey('choices', $options);
                    self::assertSame([
                        ''          => '',
                        $pluginName => [
                            'Integration 2' => 'integration-2',
                        ],
                    ], $options['choices']);
                }
            });

        $calledCallback = 0;
        $builder->expects(self::exactly(2))
            ->method('addEventListener')
            ->willReturnCallback(static function (string $eventName, callable $callback) use ($formEvent, &$calledCallback): void {
                self::assertContains($eventName, [FormEvents::PRE_SET_DATA, FormEvents::PRE_SUBMIT]);

                ++$calledCallback;
                $callback($formEvent);
            });

        $integrationsListType = new IntegrationsListType($integrationHelper);
        $integrationsListType->buildForm($builder, ['supported_features' => 'features']);

        self::assertSame(1, $callsBuilder);
        self::assertSame(4, $callsForm, 'Because callback is called twice due to coverage.');
        self::assertSame(2, $calledCallback);
    }
}
