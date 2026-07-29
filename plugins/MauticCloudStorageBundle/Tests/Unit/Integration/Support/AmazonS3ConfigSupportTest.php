<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCloudStorageBundle\Tests\Unit\Integration\Support;

use Mautic\IntegrationsBundle\Facade\EncryptionService;
use MauticPlugin\MauticCloudStorageBundle\Form\Type\AmazonS3KeysType;
use MauticPlugin\MauticCloudStorageBundle\Integration\Support\AmazonS3ConfigSupport;
use PHPUnit\Framework\TestCase;

final class AmazonS3ConfigSupportTest extends TestCase
{
    private AmazonS3ConfigSupport $configSupport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configSupport = new AmazonS3ConfigSupport($this->createStub(EncryptionService::class));
    }

    public function testGetAuthConfigFormNameReturnsAmazonS3KeysType(): void
    {
        $this->assertSame(AmazonS3KeysType::class, $this->configSupport->getAuthConfigFormName());
    }
}
