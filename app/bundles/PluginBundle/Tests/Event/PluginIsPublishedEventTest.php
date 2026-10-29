<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Mautic\PluginBundle\Event\PluginIsPublishedEvent;

final class PluginIsPublishedEventTest extends TestCase
{
    public function testSettersGetters(): void
    {
        $pluginIsPublishedEvent = new PluginIsPublishedEvent(1, 'testIntegration');

        $this->assertSame('testIntegration', $pluginIsPublishedEvent->getIntegrationName());
        $this->assertSame(1, $pluginIsPublishedEvent->getValue());
        $this->assertSame('', $pluginIsPublishedEvent->getMessage());
        $this->assertTrue($pluginIsPublishedEvent->isCanPublish());

        $pluginIsPublishedEvent->setMessage('This is test message.');
        $this->assertSame('This is test message.', $pluginIsPublishedEvent->getMessage());

        $pluginIsPublishedEvent->setCanPublish(false);
        $this->assertFalse($pluginIsPublishedEvent->isCanPublish());
    }
}
