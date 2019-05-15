<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Event\SmsSendEvent;
use Mautic\SmsBundle\EventListener\WebhookSubscriber;
use Mautic\SmsBundle\SmsEvents;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\WebhookBundle\Model\WebhookModel;

class WebhookSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $webhookModel;
    
    /**
     * @var WebhookSubscriber
     */
    private $subscriber;

    protected function setUp()
    {
        parent::setUp();

        $this->webhookModel = $this->createMock(WebhookModel::class);
        $this->subscriber   = new WebhookSubscriber($this->webhookModel);
    }

    public function testOnWebhookBuild()
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

    public function testOnSend()
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
