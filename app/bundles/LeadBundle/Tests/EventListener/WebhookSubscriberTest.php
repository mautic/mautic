<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadChangeCompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\EventListener\WebhookSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\WebhookBundle\Model\WebhookModel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class WebhookSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher;

    private LeadModel|\PHPUnit\Framework\MockObject\MockObject $leadModel;

    private WebhookModel|\PHPUnit\Framework\MockObject\MockObject $mockModel;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->mockModel  = $this->createMock(WebhookModel::class);
        $this->leadModel  = $this->createMock(LeadModel::class);
    }

    public function testNewContactEventIsFiredWhenIdentified(): void
    {
        $this->mockModel->expects($this->once())
            ->method('queueWebhooksByType')
            ->with(
                $this->callback(
                    fn ($type) => LeadEvents::LEAD_POST_SAVE.'_new' === $type
                )
            );

        $webhookSubscriber = new WebhookSubscriber($this->mockModel, $this->leadModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $lead = new Lead();
        $lead->setEmail('hello@hello.com');
        $lead->setDateIdentified(new \DateTime());
        $event = new LeadEvent($lead, true);
        $this->dispatcher->dispatch($event, LeadEvents::LEAD_POST_SAVE);
    }

    public function testUpdateContactEventIsFiredWhenUpdatedButWithoutDateIdentified(): void
    {
        $this->mockModel  = $this->createMock(WebhookModel::class);

        $this->mockModel->expects($this->once())
            ->method('queueWebhooksByType')
            ->with(
                $this->callback(
                    fn ($type) => LeadEvents::LEAD_POST_SAVE.'_update' === $type
                )
            );

        $webhookSubscriber = new WebhookSubscriber($this->mockModel, $this->leadModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $lead = new Lead();
        $lead->setEmail('hello@hello.com');
        // remove date identified so it'll simulate a simple update
        $lead->resetChanges();
        $event = new LeadEvent($lead, false);
        $this->dispatcher->dispatch($event, LeadEvents::LEAD_POST_SAVE);
    }

    public function testWebhookIsNotDeliveredIfContactIsAVisitor(): void
    {
        $this->mockModel  = $this->createMock(WebhookModel::class);

        $this->mockModel->expects($this->exactly(0))
            ->method('queueWebhooksByType');

        $webhookSubscriber = new WebhookSubscriber($this->mockModel, $this->leadModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $lead  = new Lead();
        $event = new LeadEvent($lead, false);
        $this->dispatcher->dispatch($event, LeadEvents::LEAD_POST_SAVE);
    }

    public function testWebhookIsNotDeliveredIfContactIsWithoutChanges(): void
    {
        $mockModel  = $this->createMock(WebhookModel::class);
        $leadModel  = $this->createMock(LeadModel::class);

        $mockModel->expects($this->exactly(0))
            ->method('queueWebhooksByType');

        $webhookSubscriber = new WebhookSubscriber($mockModel, $leadModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $lead  = new Lead();
        $lead->setEmail('test@test.com');
        $lead->setChanges([]);
        $event = new LeadEvent($lead, false);
        $this->dispatcher->dispatch($event, LeadEvents::LEAD_POST_SAVE);
    }

    /**
     * @testdox Test that webhook is queued for channel subscription changes
     */
    public function testChannelChangeIsPickedUpByWebhook(): void
    {
        $this->mockModel = $this->getMockBuilder(WebhookModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lead    = new Lead();
        $channel = 'email';

        $this->mockModel->expects($this->exactly(1))
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

        $webhookSubscriber = new WebhookSubscriber($this->mockModel, $this->leadModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $event = new ChannelSubscriptionChange($lead, $channel, DoNotContact::IS_CONTACTABLE, DoNotContact::UNSUBSCRIBED);
        $this->dispatcher->dispatch($event, LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED);
    }

    /**
     * @testdox Test that webhook is queued for lead company changes
     */
    public function testLeadCompanyChangeIsPickedUpByWebhook(): void
    {
        $this->mockModel = $this->getMockBuilder(WebhookModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lead    = new Lead();
        $company = new Company();

        $this->mockModel->expects($this->exactly(1))
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

        $webhookSubscriber = new WebhookSubscriber($this->mockModel, $this->leadModel);

        $this->dispatcher->addSubscriber($webhookSubscriber);

        $event = new LeadChangeCompanyEvent($lead, $company);
        $this->dispatcher->dispatch($event, LeadEvents::LEAD_COMPANY_CHANGE);
    }

    public function testOnCompanySaveAndDelete(): void
    {
        $dispatcher       = new EventDispatcher();
        $this->mockModel  = $this->createMock(WebhookModel::class);

        $this->mockModel->expects($this->exactly(2))
            ->method('queueWebhooksByType');

        $webhookSubscriber = new WebhookSubscriber($this->mockModel, $this->leadModel);

        $dispatcher->addSubscriber($webhookSubscriber);

        $company = new Company();
        $company->setName('company');
        $event = new CompanyEvent($company);
        $dispatcher->dispatch($event, LeadEvents::COMPANY_POST_SAVE);
        $dispatcher->dispatch($event, LeadEvents::COMPANY_POST_DELETE);
    }

    public function testOnSegmentChangeWithArrayContact(): void
    {
        $changeEvent = $this->createMock(ListChangeEvent::class);

        $contact       = ['id' => 1];
        $contactEntity = new Lead();
        $contactEntity->setId($contact['id']);

        $changeEvent->method('getLeads')->willReturn([$contact]);
        $changeEvent->method('getLead')->willReturn(null);
        $changeEvent->method('wasAdded')->willReturn(true);

        $leadModel    = $this->createMock(LeadModel::class);
        $webhookModel = $this->createMock(WebhookModel::class);

        $leadModel->expects($this->once())
            ->method('getEntity')
            ->with($this->equalTo($contact['id']))
            ->willReturn($contactEntity);

        $webhookModel->expects($this->once())
            ->method('queueWebhooksByType')
            ->with(
                $this->equalTo(LeadEvents::LEAD_LIST_CHANGE),
                $this->equalTo([
                    'contact'  => $contactEntity,
                    'segment'  => $changeEvent->getList(),
                    'action'   => 'added',
                ])
            );

        $example = new WebhookSubscriber($webhookModel, $leadModel);

        $example->onSegmentChange($changeEvent);
    }
}
