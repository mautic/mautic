<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;
use Mautic\LeadBundle\Event\LeadChangeCompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\EventListener\WebhookSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\WebhookBundle\Model\WebhookModel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class WebhookSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcher|MockObject
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function testNewContactEventIsFiredWhenIdentified()
    {
        $mockModel  = $this->createMock(WebhookModel::class);

        $mockModel->expects($this->once())
            ->method('queueWebhooksByType')
            ->with(
                $this->callback(
                    function ($type) {
                        return LeadEvents::LEAD_POST_SAVE.'_new' === $type;
                    }
                )
            );

        $webhookSubscriber = new WebhookSubscriber($mockModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $lead = new Lead();
        $lead->setEmail('hello@hello.com');
        $lead->setDateIdentified(new \DateTime());
        $event = new LeadEvent($lead, true);
        $this->dispatcher->dispatch(LeadEvents::LEAD_POST_SAVE, $event);
    }

    public function testUpdateContactEventIsFiredWhenUpdatedButWithoutDateIdentified()
    {
        $mockModel  = $this->createMock(WebhookModel::class);

        $mockModel->expects($this->once())
            ->method('queueWebhooksByType')
            ->with(
                $this->callback(
                    function ($type) {
                        return LeadEvents::LEAD_POST_SAVE.'_update' === $type;
                    }
                )
            );

        $webhookSubscriber = new WebhookSubscriber($mockModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $lead = new Lead();
        $lead->setEmail('hello@hello.com');
        // remove date identified so it'll simulate a simple update
        $lead->resetChanges();
        $event = new LeadEvent($lead, false);
        $this->dispatcher->dispatch(LeadEvents::LEAD_POST_SAVE, $event);
    }

    public function testWebhookIsNotDeliveredIfContactIsAVisitor()
    {
        $mockModel  = $this->createMock(WebhookModel::class);

        $mockModel->expects($this->exactly(0))
            ->method('queueWebhooksByType');

        $webhookSubscriber = new WebhookSubscriber($mockModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $lead  = new Lead();
        $event = new LeadEvent($lead, false);
        $this->dispatcher->dispatch(LeadEvents::LEAD_POST_SAVE, $event);
    }

    /**
     * @testdox Test that webhook is queued for channel subscription changes
     */
    public function testChannelChangeIsPickedUpByWebhook()
    {
        $mockModel = $this->getMockBuilder(WebhookModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lead    = new Lead();
        $channel = 'email';

        $mockModel->expects($this->exactly(1))
            ->method('queueWebhooksByType')
            ->with(
                LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED,
                [
                    'contact'    => $lead,
                    'channel'    => $channel,
                    'old_status' => 'contactable',
                    'new_status' => 'unsubscribed',
                ],

                [
                    'leadDetails',
                    'userList',
                    'publishDetails',
                    'ipAddress',
                    'doNotContactList',
                    'tagList',
                ]
            );

        $webhookSubscriber = new WebhookSubscriber($mockModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $event = new ChannelSubscriptionChange($lead, $channel, DoNotContact::IS_CONTACTABLE, DoNotContact::UNSUBSCRIBED);
        $this->dispatcher->dispatch(LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED, $event);
    }

    /**
     * @testdox Test that webhook is queued for lead company changes
     */
    public function testLeadCompanyChangeIsPickedUpByWebhook()
    {
        $mockModel = $this->getMockBuilder(WebhookModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lead    = new Lead();
        $company = new Company();

        $mockModel->expects($this->exactly(1))
            ->method('queueWebhooksByType')
            ->with(
                LeadEvents::LEAD_COMPANY_CHANGE,
                [
                    'added'      => true,
                    'contact'    => $lead,
                    'company'    => $company,
                ],
                [
                ]
            );

        $webhookSubscriber = new WebhookSubscriber($mockModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $event = new LeadChangeCompanyEvent($lead, $company);
        $this->dispatcher->dispatch(LeadEvents::LEAD_COMPANY_CHANGE, $event);
    }
}
