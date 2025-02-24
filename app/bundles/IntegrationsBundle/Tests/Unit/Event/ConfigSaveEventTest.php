<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Event;

use Mautic\IntegrationsBundle\Event\ConfigSaveEvent;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;

class ConfigSaveEventTest extends TestCase
{
    public function testGetters(): void
    {
        $name        = 'name';
        $integration = $this->createMock(Integration::class);
        $event       = new ConfigSaveEvent($integration);

        $integration->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        self::assertSame($integration, $event->getIntegrationConfiguration());
        self::assertSame($name, $event->getIntegration());
    }
}
