<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\FormType;

use Mautic\CoreBundle\Form\Type\StandAloneButtonType;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Form\Type\DetailsType;
use Mautic\PluginBundle\Form\Type\KeysType;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsTypeTest extends TestCase
{
    public function testBuildFormRemovesHiddenKeys(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = ['integration' => 'integration', 'lead_fields' => 'lead_fields', 'company_fields' => 'company_fields'];

        $integrationObject = $this->createMock(AbstractIntegration::class);
        $integrationObject->expects(self::once())
            ->method('getFormDisplaySettings')
            ->willReturn(['hide_keys' => ['key1', 'key3']]);
        $integrationObject->expects(self::once())
            ->method('getRequiredKeyFields')
            ->willReturn(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4']);
        $integrationObject->expects(self::once())
            ->method('decryptApiKeys')
            ->willReturn([]);
        $integrationObject->expects(self::never())
            ->method('isAuthorized');
        $integrationObject->expects(self::once())
            ->method('getSupportedFeatures')
            ->willReturn([]);

        $integration = $this->createMock(Integration::class);
        $integration->method('getApiKeys')
            ->willReturn([]);
        $integration->expects(self::never())
            ->method('getId');
        $integration->expects(self::never())
            ->method('getSupportedFeatures');

        $options['integration_object'] = $integrationObject;
        $options['data']               = $integration;

        $calls = 0;
        $builder->expects(self::never())
            ->method('setAction');
        $builder->expects(self::atLeastOnce())
            ->method('add')
            ->willReturnCallback(static function (string $key, string $fieldFQCN, array $options) use (&$calls): void {
                if ('apiKeys' === $key) {
                    ++$calls;
                    self::assertSame(KeysType::class, $fieldFQCN);
                    self::assertArrayHasKey('integration_keys', $options);
                    self::assertSame(['key2' => 'value2', 'key4' => 'value4'], $options['integration_keys']);
                }

                if ('authButton' === $key) {
                    ++$calls;
                }

                if ('supportedFeatures' === $key) {
                    ++$calls;
                }
            });

        $integrationObject->expects(self::once())
            ->method('modifyForm')
            ->with($builder, $options);

        $form = new DetailsType();
        $form->buildForm($builder, $options);

        self::assertSame(1, $calls);
    }

    /**
     * @dataProvider authorizedDataProvider
     */
    public function testBuildFormRequiresAuthorization(bool $isAuthorized, string $label): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = ['integration' => 'integration', 'lead_fields' => 'lead_fields', 'company_fields' => 'company_fields'];

        $integrationObject = $this->createMock(AbstractIntegration::class);
        $integrationObject->expects(self::once())
            ->method('getFormDisplaySettings')
            ->willReturn(['hide_keys' => ['key3'], 'requires_authorization' => true]);
        $integrationObject->expects(self::once())
            ->method('getRequiredKeyFields')
            ->willReturn(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4']);
        $integrationObject->expects(self::once())
            ->method('decryptApiKeys')
            ->willReturn(['decrypted']);
        $integrationObject->expects(self::once())
            ->method('isAuthorized')
            ->willReturn($isAuthorized);
        $integrationObject->expects(self::once())
            ->method('getSupportedFeatures')
            ->willReturn([]);

        $integration = $this->createMock(Integration::class);
        $integration->method('getApiKeys')
            ->willReturn([]);
        $integration->expects(self::never())
            ->method('getId');
        $integration->expects(self::never())
            ->method('getSupportedFeatures');

        $options['integration_object'] = $integrationObject;
        $options['data']               = $integration;

        $calls = 0;
        $builder->expects(self::never())
            ->method('setAction');
        $builder->expects(self::atLeastOnce())
            ->method('add')
            ->willReturnCallback(static function (string $key, string $fieldFQCN, array $options) use ($label, &$calls): void {
                if ('apiKeys' === $key) {
                    ++$calls;
                    self::assertSame(KeysType::class, $fieldFQCN);
                    self::assertArrayHasKey('integration_keys', $options);
                    self::assertSame(['key1' => 'value1', 'key2' => 'value2', 'key4' => 'value4'], $options['integration_keys']);
                }

                if ('authButton' === $key) {
                    ++$calls;
                    self::assertSame(StandAloneButtonType::class, $fieldFQCN);
                    self::assertArrayHasKey('label', $options);
                    self::assertSame('mautic.integration.form.'.$label, $options['label']);
                }

                if ('supportedFeatures' === $key) {
                    ++$calls;
                }
            });

        $integrationObject->expects(self::once())
            ->method('modifyForm')
            ->with($builder, $options);

        $form = new DetailsType();
        $form->buildForm($builder, $options);

        self::assertSame(2, $calls);
    }

    public function authorizedDataProvider(): \Generator
    {
        yield 'authorized' => [true, 'reauthorize'];
        yield 'not authorized' => [false, 'authorize'];
    }

    /**
     * @param array<string> $expectedFeatures
     * @dataProvider withFeaturesProvider
     */
    public function testBuildFormWithFeatures(?int $integrationId, array $expectedFeatures): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = ['integration' => 'integration', 'lead_fields' => 'lead_fields', 'company_fields' => 'company_fields'];

        $integrationObject = $this->createMock(AbstractIntegration::class);
        $integrationObject->expects(self::once())
            ->method('getFormDisplaySettings')
            ->willReturn(['hide_keys' => ['key1']]);
        $integrationObject->expects(self::once())
            ->method('getRequiredKeyFields')
            ->willReturn(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4']);
        $integrationObject->expects(self::once())
            ->method('decryptApiKeys')
            ->willReturn(['decrypted']);
        $integrationObject->expects(self::never())
            ->method('isAuthorized');
        $integrationObject->expects(self::once())
            ->method('getSupportedFeatures')
            ->willReturn(['non-configured']);

        $integration = $this->createMock(Integration::class);
        $integration->method('getApiKeys')
            ->willReturn([]);
        $integration->expects(self::once())
            ->method('getId')
            ->willReturn($integrationId);
        $integration->expects(self::once())
            ->method('getSupportedFeatures')
            ->willReturn(['configured']);

        $options['integration_object'] = $integrationObject;
        $options['data']               = $integration;

        $calls = 0;
        $builder->expects(self::never())
            ->method('setAction');
        $builder->expects(self::atLeastOnce())
            ->method('add')
            ->willReturnCallback(static function (string $key, string $fieldFQCN, array $options) use ($expectedFeatures, &$calls): void {
                if ('apiKeys' === $key) {
                    ++$calls;
                    self::assertSame(KeysType::class, $fieldFQCN);
                    self::assertArrayHasKey('integration_keys', $options);
                    self::assertSame(['key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4'], $options['integration_keys']);
                }

                if ('supportedFeatures' === $key) {
                    ++$calls;
                    self::assertSame(ChoiceType::class, $fieldFQCN);
                    self::assertArrayHasKey('choices', $options);
                    self::assertSame(['mautic.integration.form.feature.non-configured' => 'non-configured'], $options['choices']);
                    self::assertArrayHasKey('data', $options);
                    self::assertSame($expectedFeatures, $options['data']);
                }

                if ('authButton' === $key) {
                    ++$calls;
                }
            });

        $integrationObject->expects(self::once())
            ->method('modifyForm')
            ->with($builder, $options);

        $form = new DetailsType();
        $form->buildForm($builder, $options);

        self::assertSame(2, $calls);
    }

    public function withFeaturesProvider(): \Generator
    {
        yield 'create integration' => [null, ['non-configured']];
        yield 'edit integration' => [1, ['configured']];
    }

    public function testBuildFormWithAction(): void
    {
        $action  = 'the_action';
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = ['action' => $action, 'integration' => 'integration', 'lead_fields' => 'lead_fields', 'company_fields' => 'company_fields'];

        $integrationObject = $this->createMock(AbstractIntegration::class);
        $integrationObject->expects(self::once())
            ->method('getFormDisplaySettings')
            ->willReturn([]);
        $integrationObject->expects(self::once())
            ->method('getRequiredKeyFields')
            ->willReturn(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4']);
        $integrationObject->expects(self::once())
            ->method('decryptApiKeys')
            ->willReturn(['decrypted']);
        $integrationObject->expects(self::never())
            ->method('isAuthorized');
        $integrationObject->expects(self::once())
            ->method('getSupportedFeatures');

        $integration = $this->createMock(Integration::class);
        $integration->method('getApiKeys')
            ->willReturn([]);
        $integration->expects(self::never())
            ->method('getId');
        $integration->expects(self::never())
            ->method('getSupportedFeatures');

        $options['integration_object'] = $integrationObject;
        $options['data']               = $integration;

        $calls = 0;
        $builder->expects(self::once())
            ->method('setAction')
            ->with($action);
        $builder->expects(self::atLeastOnce())
            ->method('add')
            ->willReturnCallback(static function (string $key, string $fieldFQCN, array $options) use (&$calls): void {
                if ('apiKeys' === $key) {
                    ++$calls;
                    self::assertSame(KeysType::class, $fieldFQCN);
                    self::assertArrayHasKey('integration_keys', $options);
                    self::assertSame(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4'], $options['integration_keys']);
                }

                if ('supportedFeatures' === $key) {
                    ++$calls;
                }

                if ('authButton' === $key) {
                    ++$calls;
                }
            });

        $integrationObject->expects(self::once())
            ->method('modifyForm')
            ->with($builder, $options);

        $form = new DetailsType();
        $form->buildForm($builder, $options);

        self::assertSame(1, $calls);
    }
}
