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

class WebhookTest extends \PHPUnit_Framework_TestCase
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
}
