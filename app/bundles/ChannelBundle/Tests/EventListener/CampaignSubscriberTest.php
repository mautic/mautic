<?php

namespace Mautic\ChannelBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher;
use Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\EventListener\CampaignSubscriber;
use Mautic\ChannelBundle\Form\Type\MessageSendType;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Form\Type\EmailListType;
use Mautic\EmailBundle\Form\Type\EmailSendType;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\SmsBundle\Form\Type\SmsSendType;
use Mautic\SmsBundle\SmsEvents;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CampaignSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|MessageModel
     */
    private $messageModel;

    /**
     * @var ActionDispatcher
     */
    private $eventDispatcher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventCollector
     */
    private $eventCollector;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Translator
     */
    private $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventScheduler
     */
    private $scheduler;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LegacyEventDispatcher
     */
    private $legacyDispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();

        $this->messageModel = $this->getMockBuilder(MessageModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageModel->method('getChannels')
            ->willReturn(
                [
                    'email' => [
                        'campaignAction'             => 'email.send',
                        'campaignDecisionsSupported' => [
                            'email.open',
                            'page.pagehit',
                            'asset.download',
                            'form.submit',
                        ],
                        'lookupFormType'             => EmailListType::class,
                    ],
                    'sms'   => [
                        'campaignAction'             => 'sms.send_text_sms',
                        'campaignDecisionsSupported' => [
                            'page.pagehit',
                            'asset.download',
                            'form.submit',
                        ],
                        'lookupFormType'             => 'sms_list',
                        'repository'                 => 'MauticSmsBundle:Sms',
                    ],
                ]
            );

        $this->messageModel->method('getMessageChannels')
            ->willReturn(
                [
                    'email' => [
                        'id'         => 2,
                        'channel'    => 'email',
                        'channel_id' => 2,
                        'properties' => [],
                    ],
                    'sms'   => [
                        'id'         => 1,
                        'channel'    => 'sms',
                        'channel_id' => 1,
                        'properties' => [],
                    ],
                ]
            );

        $this->scheduler = $this->getMockBuilder(EventScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $notificationHelper = $this->getMockBuilder(NotificationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contactTracker = $this->getMockBuilder(ContactTracker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->legacyDispatcher = new LegacyEventDispatcher(
            $this->dispatcher,
            $this->scheduler,
            new NullLogger(),
            $notificationHelper,
            $factory,
            $contactTracker
        );

        $this->eventDispatcher = new ActionDispatcher(
            $this->dispatcher,
            new NullLogger(),
            $this->scheduler,
            $notificationHelper,
            $this->legacyDispatcher
        );

        $this->eventCollector = $this->getMockBuilder(EventCollector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventCollector->method('getEventConfig')
            ->willReturnCallback(
                function (Event $event) {
                    switch ($event->getType()) {
                        case 'email.send':
                            return new ActionAccessor(
                                [
                                    'label'                => 'mautic.email.campaign.event.send',
                                    'description'          => 'mautic.email.campaign.event.send_descr',
                                    'batchEventName'       => EmailEvents::ON_CAMPAIGN_BATCH_ACTION,
                                    'formType'             => EmailSendType::class,
                                    'formTypeOptions'      => ['update_select' => 'campaignevent_properties_email', 'with_email_types' => true],
                                    'formTheme'            => 'MauticEmailBundle:FormTheme\EmailSendList',
                                    'channel'              => 'email',
                                    'channelIdField'       => 'email',
                                ]
                            );

                        case 'sms.send_text_sms':
                            return new ActionAccessor(
                                [
                                    'label'            => 'mautic.campaign.sms.send_text_sms',
                                    'description'      => 'mautic.campaign.sms.send_text_sms.tooltip',
                                    'eventName'        => SmsEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                                    'formType'         => SmsSendType::class,
                                    'formTypeOptions'  => ['update_select' => 'campaignevent_properties_sms'],
                                    'formTheme'        => 'MauticSmsBundle:FormTheme\SmsSendList',
                                    'timelineTemplate' => 'MauticSmsBundle:SubscribedEvents\Timeline:index.html.php',
                                    'channel'          => 'sms',
                                    'channelIdField'   => 'sms',
                                ]
                            );
                    }
                }
            );

        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $campaignSubscriber = new CampaignSubscriber(
            $this->messageModel,
            $this->eventDispatcher,
            $this->eventCollector,
            new NullLogger(),
            $this->translator
        );

        $this->dispatcher->addSubscriber($campaignSubscriber);
        $this->dispatcher->addListener(EmailEvents::ON_CAMPAIGN_BATCH_ACTION, [$this, 'sendMarketingMessageEmail']);
        $this->dispatcher->addListener(SmsEvents::ON_CAMPAIGN_TRIGGER_ACTION, [$this, 'sendMarketingMessageSms']);
    }

    public function testCorrectChannelIsUsed()
    {
        $event  = $this->getEvent();
        $config = new ActionAccessor(
            [
                'label'                  => 'mautic.channel.message.send.marketing.message',
                'description'            => 'mautic.channel.message.send.marketing.message.descr',
                'batchEventName'         => ChannelEvents::ON_CAMPAIGN_BATCH_ACTION,
                'formType'               => MessageSendType::class,
                'formTheme'              => 'MauticChannelBundle:FormTheme\MessageSend',
                'channel'                => 'channel.message',
                'channelIdField'         => 'marketingMessage',
                'connectionRestrictions' => [
                    'target' => [
                        'decision' => [
                            'email.open',
                            'page.pagehit',
                            'asset.download',
                            'form.submit',
                        ],
                    ],
                ],
                'timelineTemplate'       => 'MauticChannelBundle:SubscribedEvents\Timeline:index.html.php',
                'timelineTemplateVars'   => [
                    'messageSettings' => [],
                ],
            ]
        );
        $logs   = $this->getLogs();

        $pendingEvent = new PendingEvent($config, $event, $logs);

        $this->dispatcher->dispatch(ChannelEvents::ON_CAMPAIGN_BATCH_ACTION, $pendingEvent);

        $this->assertCount(0, $pendingEvent->getFailures());

        $successful = $pendingEvent->getSuccessful();

        // SMS should be noted as DNC
        $this->assertFalse(empty($successful->get(2)->getMetadata()['sms']['dnc']));

        // Nothing recorded for success
        $this->assertTrue(empty($successful->get(1)->getMetadata()));
    }

    public function sendMarketingMessageEmail(PendingEvent $event)
    {
        $contacts = $event->getContacts();
        $logs     = $event->getPending();
        $this->assertCount(1, $logs);

        if (1 === $contacts->first()->getId()) {
            // Processing priority 1 for contact 1, let's fail this one so that SMS is used
            $event->fail($logs->first(), 'just because');

            return;
        }

        if (2 === $contacts->first()->getId()) {
            // Processing priority 1 for contact 2 so let's pass it
            $event->pass($logs->first());

            return;
        }
    }

    /**
     * BC support for old campaign.
     */
    public function sendMarketingMessageSms(CampaignExecutionEvent $event)
    {
        $lead = $event->getLead();
        if (1 === $lead->getId()) {
            $event->setResult(true);

            return;
        }

        if (2 === $lead->getId()) {
            $this->fail('Lead ID 2 is unsubscribed from SMS so this shouldn not have happened.');
        }
    }

    /**
     * @return Event|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEvent()
    {
        $event = $this->getMockBuilder(Event::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $event->method('getId')
            ->willReturn(1);
        $event->setEventType(Event::TYPE_ACTION);
        $event->setType('message.send');
        $event->setChannel('channel.message');
        $event->setChannelId(1);
        $event->setProperties(
            [
                'canvasSettings'      => [
                        'droppedX' => '337',
                        'droppedY' => '155',
                    ],
                'name'                => '',
                'triggerMode'         => 'immediate',
                'triggerDate'         => null,
                'triggerInterval'     => '1',
                'triggerIntervalUnit' => 'd',
                'anchor'              => 'leadsource',
                'properties'          => [
                        'marketingMessage' => '1',
                    ],
                'type'                => 'message.send',
                'eventType'           => 'action',
                'anchorEventType'     => 'source',
                'campaignId'          => '1',
                '_token'              => 'q7FpcDX7iye6fBuBzsqMvQWKqW75lcD77jSmuNAEDXg',
                'buttons'             => [
                        'save' => '',
                    ],
                'marketingMessage'    => '1',
            ]
        );
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->method('getId')
            ->willReturn(1);

        $event->setCampaign($campaign);

        return $event;
    }

    /**
     * @return ArrayCollection
     */
    private function getLogs()
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->method('getId')
            ->willReturn(1);
        $lead->expects($this->once())
            ->method('getChannelRules')
            ->willReturn(
                [
                    'sms' => [
                        'dnc' => DoNotContact::IS_CONTACTABLE,
                    ],
                    'email' => [
                        'dnc' => DoNotContact::IS_CONTACTABLE,
                    ],
                ]
            );

        $log = $this->getMockBuilder(LeadEventLog::class)
            ->onlyMethods(['getLead', 'getId'])
            ->getMock();
        $log->method('getLead')
            ->willReturn($lead);
        $log->method('getId')
            ->willReturn(1);

        $lead2 = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead2->method('getId')
            ->willReturn(2);
        $lead2->expects($this->once())
            ->method('getChannelRules')
            ->willReturn(
                [
                    'email' => [
                        'dnc' => DoNotContact::IS_CONTACTABLE,
                    ],
                    'sms' => [
                        'dnc' => DoNotContact::UNSUBSCRIBED,
                    ],
                ]
            );

        $log2 = $this->getMockBuilder(LeadEventLog::class)
            ->onlyMethods(['getLead', 'getId'])
            ->getMock();
        $log2->method('getLead')
            ->willReturn($lead2);
        $log2->method('getId')
            ->willReturn(2);

        return new ArrayCollection([1 => $log, 2 => $log2]);
    }
}
