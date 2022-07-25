<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Event\SmsSendEvent;
use Mautic\SmsBundle\EventListener\WebhookSubscriber;
use Mautic\SmsBundle\SmsEvents;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\MockObject\MockObject;

final class WebhookSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|WebhookModel
     */
    private $webhookModel;

    /**
     * @var WebhookSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookModel = $this->createMock(WebhookModel::class);
        $this->subscriber   = new WebhookSubscriber($this->webhookModel);
    }

    public function testOnWebhookBuild(): void
    {
        $event = $this->createMock(WebhookBuilderEvent::class);

        $event->expects($this->once())
            ->method('addEvent')
            ->with(
                SmsEvents::SMS_ON_SEND,
                [
                    'label'       => 'mautic.sms.webhook.event.send',
                    'description' => 'mautic.sms.webhook.event.send_desc',
                ]
            );

        $this->subscriber->onWebhookBuild($event);
    }

    public function testOnSend(): void
    {
        $event   = $this->createMock(SmsSendEvent::class);
        $contact = $this->createMock(Lead::class);

        $event->expects($this->once())
            ->method('getSmsId')
            ->willReturn(343);

        $event->expects($this->once())
            ->method('getLead')
            ->willReturn($contact);

        $event->expects($this->once())
            ->method('getContent')
            ->willReturn('The SMS content.');

        $this->webhookModel->expects($this->once())
            ->method('queueWebhooksByType')
            ->with(
                SmsEvents::SMS_ON_SEND,
                [
                    'smsId'   => 343,
                    'contact' => $contact,
                    'content' => 'The SMS content.',
                ]
            );

        $this->subscriber->onSend($event);
    }
}
