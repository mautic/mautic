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
    public function testSupportedFeaturesChoicesUseTranslatableLabelsWithRealSlugAsValue(): void
    {
        $integrationObject = $this->createMock(ConfigFormFeaturesInterface::class);
        $integrationObject->method('getSupportedFeatures')->willReturn(['cloud_storage', 'other_feature']);

        $integrationsHelper = $this->createMock(ConfigIntegrationsHelper::class);
        $integrationsHelper->method('getIntegration')->with('AmazonS3')->willReturn($integrationObject);

        /** @var FormBuilderInterface&MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(function (string $name, string $type, array $options = []) use ($builder) {
            if ('supportedFeatures' === $name) {
                $this->assertSame(ChoiceType::class, $type);
                $this->assertSame(
                    [
                        'mautic.integration.form.feature.cloud_storage'  => 'cloud_storage',
                        'mautic.integration.form.feature.other_feature' => 'other_feature',
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
