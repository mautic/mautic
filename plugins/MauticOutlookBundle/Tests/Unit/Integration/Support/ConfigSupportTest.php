<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOutlookBundle\Tests\Unit\Integration\Support;

use MauticPlugin\MauticOutlookBundle\Form\Type\OutlookKeysType;
use MauticPlugin\MauticOutlookBundle\Integration\Support\ConfigSupport;
use PHPUnit\Framework\TestCase;

final class ConfigSupportTest extends TestCase
{
    private ConfigSupport $configSupport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configSupport = new ConfigSupport();
    }

    public function testGetAuthConfigFormNameReturnsOutlookKeysType(): void
    {
        $this->assertSame(OutlookKeysType::class, $this->configSupport->getAuthConfigFormName());
    }

    public function testGetConfigFormContentTemplateReturnsExpectedTemplate(): void
    {
        $this->assertSame('@MauticOutlook/Integration/config_form.html.twig', $this->configSupport->getConfigFormContentTemplate());
    }
}
