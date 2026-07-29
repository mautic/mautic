<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Form\Type;

use Mautic\IntegrationsBundle\Form\Type\IntegrationConfigType;
use Mautic\IntegrationsBundle\Helper\ConfigIntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class IntegrationConfigTypeTest extends TestCase
{
    public function testSupportedFeaturesChoicesFlipSlugLabelPairsIntoLabelSlugChoices(): void
    {
        $integrationObject = $this->createMock(ConfigFormFeaturesInterface::class);
        $integrationObject->method('getSupportedFeatures')->willReturn([
            ConfigFormFeaturesInterface::FEATURE_CLOUD_STORAGE => 'mautic.integration.form.feature.cloud_storage',
            ConfigFormFeaturesInterface::FEATURE_SYNC          => 'mautic.integration.feature.sync',
        ]);

        $integrationsHelper = $this->createMock(ConfigIntegrationsHelper::class);
        $integrationsHelper->method('getIntegration')->with('AmazonS3')->willReturn($integrationObject);

        /** @var FormBuilderInterface&MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(function (string $name, string $type, array $options = []) use ($builder) {
            if ('supportedFeatures' === $name) {
                $this->assertSame(ChoiceType::class, $type);
                $this->assertSame(
                    [
                        'mautic.integration.form.feature.cloud_storage' => ConfigFormFeaturesInterface::FEATURE_CLOUD_STORAGE,
                        'mautic.integration.feature.sync'               => ConfigFormFeaturesInterface::FEATURE_SYNC,
                    ],
                    $options['choices']
                );
            }

            return $builder;
        });
        $builder->method('setAction')->willReturnSelf();

        $formType = new IntegrationConfigType($integrationsHelper);
        $formType->buildForm($builder, ['integration' => 'AmazonS3', 'action' => '/test']);
    }
}
