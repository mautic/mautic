<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOutlookBundle\Tests\Unit\Integration;

use MauticPlugin\MauticOutlookBundle\Integration\OutlookIntegration;
use PHPUnit\Framework\TestCase;

final class OutlookIntegrationTest extends TestCase
{
    private OutlookIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = new OutlookIntegration();
    }

    public function testGetNameReturnsOutlook(): void
    {
        $this->assertSame('Outlook', $this->integration->getName());
    }

    public function testGetDisplayNameReturnsOutlook(): void
    {
        $this->assertSame('Outlook', $this->integration->getDisplayName());
    }

    public function testGetIconReturnsExpectedPath(): void
    {
        $this->assertSame('plugins/MauticOutlookBundle/Assets/img/outlook.png', $this->integration->getIcon());
    }
}
