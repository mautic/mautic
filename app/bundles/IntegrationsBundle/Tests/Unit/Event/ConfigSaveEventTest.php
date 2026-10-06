<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Event;

use Mautic\IntegrationsBundle\Event\ConfigBeforeSaveEvent;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;

final class ConfigSaveEventTest extends TestCase
{
    public function testGetters(): void
    {
        $name        = 'name';
        $integration = $this->createMock(Integration::class);
        $event       = new ConfigBeforeSaveEvent($integration);

        $integration->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->assertSame($integration, $event->getIntegrationConfiguration());
        $this->assertSame($name, $event->getIntegration());
    }
}
