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
        $integration = new Integration();
        $integration->setName($name);
        $event       = new ConfigBeforeSaveEvent($integration);

        $this->assertSame($integration, $event->getIntegrationConfiguration());
        $this->assertSame($name, $event->getIntegration());
    }
}
