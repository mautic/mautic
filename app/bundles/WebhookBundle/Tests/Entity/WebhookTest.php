<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Tests\Entity;

use Mautic\WebhookBundle\Entity\Webhook;
use PHPUnit\Framework\Assert;

class WebhookTest extends \PHPUnit\Framework\TestCase
{
    public function testWasModifiedRecentlyWithNotModifiedWebhook()
    {
        $webhook = new Webhook();
        $this->assertNull($webhook->getDateModified());
        $this->assertFalse($webhook->wasModifiedRecently());
    }

    public function testWasModifiedRecentlyWithWebhookModifiedAWhileBack()
    {
        $webhook = new Webhook();
        $webhook->setDateModified((new \DateTime())->modify('-20 days'));
        $this->assertFalse($webhook->wasModifiedRecently());
    }

    public function testWasModifiedRecentlyWithWebhookModifiedRecently()
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
        Assert::assertCount(3, $events);

        foreach ($events as $key => $event) {
            Assert::assertEquals($event->getEventType(), $triggers[$key]);
            Assert::assertSame($webhook, $event->getWebhook());
        }
    }
}
