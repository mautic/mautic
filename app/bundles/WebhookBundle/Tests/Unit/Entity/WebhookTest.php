<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Unit\Entity;

use Mautic\WebhookBundle\Entity\Webhook;

final class WebhookTest extends \PHPUnit\Framework\TestCase
{
    public function testWasModifiedRecentlyWithNotModifiedWebhook(): void
    {
        $webhook = new Webhook();
        $this->assertNotInstanceOf(\DateTimeInterface::class, $webhook->getDateModified());
        $this->assertFalse($webhook->wasModifiedRecently());
    }

    public function testWasModifiedRecentlyWithWebhookModifiedAWhileBack(): void
    {
        $webhook = new Webhook();
        $webhook->setDateModified((new \DateTime())->modify('-20 days'));
        $this->assertFalse($webhook->wasModifiedRecently());
    }

    public function testWasModifiedRecentlyWithWebhookModifiedRecently(): void
    {
        $webhook = new Webhook();
        $webhook->setDateModified((new \DateTime())->modify('-2 hours'));
        $this->assertTrue($webhook->wasModifiedRecently());
    }

    public function testTriggersFromApiAreStoredAsEvents(): void
    {
        $webhook  = new Webhook();
        $triggers = [
            'mautic.company_post_save',
            'mautic.company_post_delete',
            'mautic.lead_channel_subscription_changed',
        ];

        $webhook->setTriggers($triggers);

        $events = $webhook->getEvents();
        $this->assertCount(3, $events);

        foreach ($events as $key => $event) {
            $this->assertEquals($event->getEventType(), $triggers[$key]);
            $this->assertSame($webhook, $event->getWebhook());
        }
    }
}
