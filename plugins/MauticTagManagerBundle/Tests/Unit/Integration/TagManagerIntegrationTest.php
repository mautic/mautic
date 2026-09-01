<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Unit\Integration;

use MauticPlugin\MauticTagManagerBundle\Integration\TagManagerIntegration;
use PHPUnit\Framework\TestCase;

final class TagManagerIntegrationTest extends TestCase
{
    private TagManagerIntegration $tagManagerIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagManagerIntegration = new TagManagerIntegration();
    }

    public function testGetNameReturnsName(): void
    {
        $name = $this->tagManagerIntegration->getName();
        $this->assertSame(TagManagerIntegration::PLUGIN_NAME, $name);
    }

    public function testGetDisplayNameReturnsName(): void
    {
        $displayName = $this->tagManagerIntegration->getDisplayName();
        $this->assertNotEmpty($displayName);
    }

    public function testGetIconReturnsExpectedPath(): void
    {
        $this->assertSame('plugins/MauticTagManagerBundle/Assets/img/tagmanager.png', $this->tagManagerIntegration->getIcon());
    }
}
