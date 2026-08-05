<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCloudStorageBundle\Tests\Unit\Integration;

use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticCloudStorageBundle\Exception\InvalidCredentialConfigurationException;
use MauticPlugin\MauticCloudStorageBundle\Integration\AmazonS3Integration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AmazonS3IntegrationTest extends TestCase
{
    private EncryptionService&MockObject $encryptionService;

    private AmazonS3Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryptionService = $this->createMock(EncryptionService::class);
        $this->integration       = new AmazonS3Integration($this->encryptionService);
    }

    public function testGetNameReturnsAmazonS3(): void
    {
        $this->assertSame('AmazonS3', $this->integration->getName());
    }

    public function testGetDisplayNameReturnsAmazonS3(): void
    {
        $this->assertSame('Amazon S3', $this->integration->getDisplayName());
    }

    public function testGetIconReturnsExpectedPath(): void
    {
        $this->assertSame('plugins/MauticCloudStorageBundle/Assets/img/amazons3.png', $this->integration->getIcon());
    }

    public function testGetSupportedFeaturesReturnsCloudStorage(): void
    {
        $this->assertSame([ConfigFormFeaturesInterface::FEATURE_CLOUD_STORAGE => 'mautic.integration.form.feature.cloud_storage'], $this->integration->getSupportedFeatures());
    }

    public function testGetAdapterThrowsExceptionWhenCredentialsMissing(): void
    {
        $configuration = new Integration();
        $configuration->setApiKeys([]);
        $this->integration->setIntegrationConfiguration($configuration);

        $this->encryptionService->method('decrypt')->willReturn([]);

        $this->expectException(InvalidCredentialConfigurationException::class);

        $this->integration->getAdapter();
    }
}
