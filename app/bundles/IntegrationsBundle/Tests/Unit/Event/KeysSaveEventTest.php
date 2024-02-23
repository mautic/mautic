<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Event;

use Mautic\IntegrationsBundle\Event\KeysSaveEvent;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;

class KeysSaveEventTest extends TestCase
{
    public function testGetters(): void
    {
        $integration = $this->createMock(Integration::class);
        $keys        = ['apikey' => 'test'];
        $integration->expects(self::once())
            ->method('getApiKeys')
            ->willReturn($keys);

        $event = new KeysSaveEvent($integration, $keys);

        self::assertSame($integration, $event->getIntegrationConfiguration());
        self::assertSame($keys, $event->getOldKeys());
        self::assertSame($keys, $event->getNewKeys());
    }
}
