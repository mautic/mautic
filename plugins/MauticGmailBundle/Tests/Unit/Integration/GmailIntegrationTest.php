<?php

declare(strict_types=1);

namespace MauticPlugin\MauticGmailBundle\Tests\Unit\Integration;

use MauticPlugin\MauticGmailBundle\Integration\GmailIntegration;
use PHPUnit\Framework\TestCase;

final class GmailIntegrationTest extends TestCase
{
    private GmailIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = new GmailIntegration();
    }

    public function testGetNameReturnsGmail(): void
    {
        $this->assertSame('Gmail', $this->integration->getName());
    }

    public function testGetDisplayNameReturnsGmail(): void
    {
        $this->assertSame('Gmail', $this->integration->getDisplayName());
    }

    public function testGetIconReturnsExpectedPath(): void
    {
        $this->assertSame('plugins/MauticGmailBundle/Assets/img/gmail.png', $this->integration->getIcon());
    }
}
