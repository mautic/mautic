<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Event;

use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Event\PluginUninstallEvent;
use PHPUnit\Framework\TestCase;

class PluginUninstallEventTest extends TestCase
{
    public function testGetPlugin(): void
    {
        $plugin = new Plugin();
        $plugin->setName('TestPlugin');

        $event = new PluginUninstallEvent($plugin);

        $this->assertSame($plugin, $event->getPlugin());
    }

    public function testCheckContext(): void
    {
        $plugin = new Plugin();
        $plugin->setName('TestPlugin');

        $event = new PluginUninstallEvent($plugin);

        $this->assertTrue($event->checkContext('TestPlugin'));
        $this->assertFalse($event->checkContext('OtherPlugin'));
    }
}
