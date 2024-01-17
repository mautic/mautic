<?php

namespace Mautic\WebhookBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Event\WebhookEvent;
use Mautic\WebhookBundle\EventListener\WebhookSubscriber;
use Mautic\WebhookBundle\Notificator\WebhookKillNotificator;
use Mautic\WebhookBundle\WebhookEvents;
use PHPUnit\Framework\MockObject\MockObject;

class WebhookSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&IpLookupHelper
     */
    private MockObject $ipLookupHelper;

    /**
     * @var MockObject&AuditLogModel
     */
    private MockObject $auditLogModel;

    /**
     * @var MockObject&WebhookKillNotificator
     */
    private MockObject $webhookKillNotificator;

    protected function setUp(): void
    {
        $this->ipLookupHelper         = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel          = $this->createMock(AuditLogModel::class);
        $this->webhookKillNotificator = $this->createMock(WebhookKillNotificator::class);
    }

    public function testGetSubscribedEvents(): void
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

    public function testOnWebhookKill(): void
    {
        $webhookMock = $this->createMock(Webhook::class);
        $reason      = 'reason';

        $eventMock = $this->createMock(WebhookEvent::class);
        $eventMock
            ->expects($this->once())
            ->method('getWebhook')
            ->willReturn($webhookMock);
        $eventMock
            ->expects($this->once())
            ->method('getReason')
            ->willReturn($reason);

        $this->webhookKillNotificator
            ->expects($this->once())
            ->method('send')
            ->with($webhookMock, $reason);

        $subscriber = new WebhookSubscriber($this->ipLookupHelper, $this->auditLogModel, $this->webhookKillNotificator);
        $subscriber->onWebhookKill($eventMock);
    }
}
