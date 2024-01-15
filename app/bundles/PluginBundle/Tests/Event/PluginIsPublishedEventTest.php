<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Event;

use Mautic\PluginBundle\Event\PluginIsPublishedEvent;

class PluginIsPublishedEventTest extends \PHPUnit\Framework\TestCase
{
    public function testInstallPlugins(): void
    {
        $pluginIsPublishedEvent = new PluginIsPublishedEvent(1, 'testIntegration');

        $this->assertSame('testIntegration', $pluginIsPublishedEvent->getIntegrationName());
        $this->assertSame(1, $pluginIsPublishedEvent->getValue());
        $this->assertSame('', $pluginIsPublishedEvent->getMessage());
        $this->assertSame(true, $pluginIsPublishedEvent->isCanPublish());

        $pluginIsPublishedEvent->setMessage('This is test message.');
        $this->assertSame('This is test message.', $pluginIsPublishedEvent->getMessage());

        $pluginIsPublishedEvent->setCanPublish(false);
        $this->assertSame(false, $pluginIsPublishedEvent->isCanPublish());
    }
}
