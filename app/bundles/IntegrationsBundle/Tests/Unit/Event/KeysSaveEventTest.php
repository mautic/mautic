<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Event;

use Mautic\IntegrationsBundle\Event\KeysSaveEvent;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;

final class KeysSaveEventTest extends TestCase
{
    public function testGetters(): void
    {
        $integration = $this->createMock(Integration::class);
        $keys        = ['apikey' => 'test'];
        $integration->expects($this->once())
            ->method('getApiKeys')
            ->willReturn($keys);

        $event = new KeysSaveEvent($integration, $keys);

        $this->assertSame($integration, $event->getIntegrationConfiguration());
        $this->assertSame($keys, $event->getOldKeys());
        $this->assertSame($keys, $event->getNewKeys());
    }
}
