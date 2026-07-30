<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Tests\Unit\Integration;

use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use PHPUnit\Framework\TestCase;

final class FullContactIntegrationTest extends TestCase
{
    private FullContactIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = new FullContactIntegration();
    }

    public function testGetNameReturnsFullContact(): void
    {
        $this->assertSame('FullContact', $this->integration->getName());
    }

    public function testGetDisplayNameReturnsFullContact(): void
    {
        $this->assertSame('FullContact', $this->integration->getDisplayName());
    }

    public function testGetIconReturnsExpectedPath(): void
    {
        $this->assertSame('plugins/MauticFullContactBundle/Assets/img/fullcontact.png', $this->integration->getIcon());
    }

    public function testShouldAutoUpdateReturnsFalseWhenNoConfigurationSet(): void
    {
        $this->assertFalse($this->integration->shouldAutoUpdate());
    }

    public function testShouldAutoUpdateReturnsFalseWhenDisabled(): void
    {
        $this->integration->setIntegrationConfiguration($this->makeConfiguration(['auto_update' => '0']));

        $this->assertFalse($this->integration->shouldAutoUpdate());
    }

    public function testShouldAutoUpdateReturnsTrueWhenEnabled(): void
    {
        $this->integration->setIntegrationConfiguration($this->makeConfiguration(['auto_update' => '1']));

        $this->assertTrue($this->integration->shouldAutoUpdate());
    }

    /**
     * @param array<string, mixed> $apiKeys
     */
    private function makeConfiguration(array $apiKeys): Integration
    {
        $configuration = new Integration();
        $configuration->setApiKeys($apiKeys);

        return $configuration;
    }
}
