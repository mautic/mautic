<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\WebhookBundle\Tests\EvenzListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\WebhookBundle\Event\WebhookEvent;
use Mautic\WebhookBundle\EventListener\WebhookSubscriber;
use Mautic\WebhookBundle\Notificator\WebhookKillNotificator;
use Mautic\WebhookBundle\WebhookEvents;

class WebhookSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $ipLookupHelper;
    private $auditLogModel;
    private $webhookKillNotificator;

    protected function setUp()
    {
        $this->ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel = $this->createMock(AuditLogModel::class);
        $this->webhookKillNotificator = $this->createMock(WebhookKillNotificator::class);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [
                WebhookEvents::WEBHOOK_POST_SAVE   => ['onWebhookSave', 0],
                WebhookEvents::WEBHOOK_POST_DELETE => ['onWebhookDelete', 0],
                WebhookEvents::WEBHOOK_KILL        => ['onWebhookKill', 0],
            ],
            WebhookSubscriber::getSubscribedEvents()
        );
    }

    public function testOnWebhookKill()
    {

    }
}
