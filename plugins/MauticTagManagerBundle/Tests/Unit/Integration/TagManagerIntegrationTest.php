<?php

namespace MauticPlugin\MauticTagManagerBundle\Tests\Unit\Integration;

use MauticPlugin\MauticTagManagerBundle\Integration\TagManagerIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class TagManagerIntegrationTest extends TestCase
{
    private TagManagerIntegration $tagManagerIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagManagerIntegration = new class extends TagManagerIntegration {
            public function __construct()
            {
            }
        };
    }

    public function testGetNameReturnsName(): void
    {
        $name = $this->tagManagerIntegration->getName();
        Assert::assertSame(TagManagerIntegration::PLUGIN_NAME, $name);
    }

    public function testGetDisplayNameReturnsName(): void
    {
        $displayName = $this->tagManagerIntegration->getDisplayName();
        Assert::assertNotEmpty($displayName);
    }

    public function testGetAuthenticationTypeReturnsNonEmptyValue(): void
    {
        $authenticationType = $this->tagManagerIntegration->getAuthenticationType();
        Assert::assertNotEmpty($authenticationType);
    }
}
