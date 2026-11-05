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
        $integration = new Integration();
        $keys        = ['apikey' => 'test'];
        $integration->setApiKeys($keys);

        $event = new KeysSaveEvent($integration, $keys);

        $this->assertSame($integration, $event->getIntegrationConfiguration());
        $this->assertSame($keys, $event->getOldKeys());
        $this->assertSame($keys, $event->getNewKeys());
    }
}
