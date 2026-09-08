<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\EventListener\CampaignActionJumpToEventSubscriber;
use Mautic\CampaignBundle\Executioner\Event\ActionExecutioner;
use Mautic\CampaignBundle\Executioner\Event\ConditionExecutioner;
use Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Form\Type\CampaignEventJumpToEventType;
use Mautic\CampaignBundle\Helper\RemovedContactTracker;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Form\Type\EmailSendType;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class EventExecutionerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&EventCollector
     */
    private MockObject $eventCollector;

    /**
     * @var MockObject&EventLogger
     */
    private MockObject $eventLogger;

    /**
     * @var MockObject&ActionExecutioner
     */
    private MockObject $actionExecutioner;

    /**
     * @var MockObject&EventScheduler
     */
    private MockObject $eventScheduler;

    /**
     * @var MockObject&LeadRepository
     */
    private MockObject $leadRepository;

    /**
     * @var MockObject&EventRepository
     */
    private MockObject $eventRepository;

    protected function setUp(): void
    {
        $this->eventCollector        = $this->createMock(EventCollector::class);
        $this->eventLogger           = $this->createMock(EventLogger::class);
        $this->eventLogger->method('persistCollection')
            ->willReturn($this->eventLogger);
        $this->actionExecutioner     = $this->createMock(ActionExecutioner::class);
        $this->eventScheduler        = $this->createMock(EventScheduler::class);
        $this->leadRepository        = $this->createMock(LeadRepository::class);
        $this->eventRepository       = $this->createMock(EventRepository::class);
    }

    public function testJumpToEventsAreProcessedAfterOtherEvents(): void
    {
        $campaign = new Campaign();

        $otherEvent = new Event();
        $otherEvent->setEventType(ActionExecutioner::TYPE)
            ->setType('email.send')
            ->setCampaign($campaign);
        $otherConfig = new ActionAccessor(
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

        $jumpEvent = new Event();
        $jumpEvent->setEventType(ActionExecutioner::TYPE)
            ->setType(CampaignActionJumpToEventSubscriber::EVENT_NAME)
            ->setCampaign($campaign);
        $jumpConfig = new ActionAccessor(
            [
                'label'                  => 'mautic.campaign.event.jump_to_event',
                'description'            => 'mautic.campaign.event.jump_to_event_descr',
                'formType'               => CampaignEventJumpToEventType::class,
                'template'               => '@MauticCampaign/Event/jump.html.twig',
                'batchEventName'         => CampaignEvents::ON_EVENT_JUMP_TO_EVENT,
                'connectionRestrictions' => [
                    'target' => [
                        Event::TYPE_DECISION  => ['none'],
                        Event::TYPE_ACTION    => ['none'],
                        Event::TYPE_CONDITION => ['none'],
                    ],
                ],
            ]
        );

        $events   = new ArrayCollection([$otherEvent, $jumpEvent]);
        $contacts = new ArrayCollection([new Lead()]);

        $this->eventCollector->method('getEventConfig')
            ->willReturnCallback(
                function (Event $event) use ($jumpConfig, $otherConfig): ActionAccessor {
                    if (CampaignActionJumpToEventSubscriber::EVENT_NAME === $event->getType()) {
                        return $jumpConfig;
                    }

                    return $otherConfig;
                }
            );

        $this->eventScheduler->expects($this->exactly(2))
            ->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        $this->eventLogger->expects($this->exactly(2))
            ->method('fetchRotationAndGenerateLogsFromContacts')
            ->willReturnCallback(
                function (Event $event, ActionAccessor $config, ArrayCollection $contacts, $isInactiveEntry): ArrayCollection {
                    $logs = new ArrayCollection();
                    foreach ($contacts as $contact) {
                        $log = new LeadEventLog();
                        $log->setLead($contact);
                        $log->setEvent($event);
                        $log->setCampaign($event->getCampaign());
                        $logs->add($log);
                    }

                    return $logs;
                }
            );
        $matcher = $this->exactly(2);

        $this->actionExecutioner->expects($matcher)
            ->method('execute')->willReturnCallback(function (...$parameters) use ($matcher, $otherConfig, $jumpConfig): EvaluatedContacts {
                $this->assertInstanceOf(ArrayCollection::class, $parameters[1]);
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals($otherConfig, $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertEquals($jumpConfig, $parameters[0]);
                }

                return new EvaluatedContacts();
            });

        // This should not be called because the rotation is already incremented in the subscriber
        $this->leadRepository->expects($this->never())
            ->method('incrementCampaignRotationForContacts');

        $this->getEventExecutioner()->executeEventsForContacts($events, $contacts);
    }

    private function getEventExecutioner(): EventExecutioner
    {
        return new EventExecutioner(
            $this->eventCollector,
            $this->eventLogger,
            $this->actionExecutioner,
            $this->createStub(ConditionExecutioner::class),
            $this->createStub(DecisionExecutioner::class),
            $this->createStub(LoggerInterface::class),
            $this->eventScheduler,
            $this->createStub(RemovedContactTracker::class),
        );
    }

    public function testJumpToEventsExecutedWithoutTarget(): void
    {
        $campaign = new Campaign();

        $event = new Event();
        $event->setEventType(ActionExecutioner::TYPE)
            ->setType(CampaignActionJumpToEventSubscriber::EVENT_NAME)
            ->setCampaign($campaign)
            ->setProperties(['jumpToEvent' => 999]);

        $lead = $this->createMock(Lead::class);
        $lead->method('getId')
            ->willReturn(1);

        $log = $this->createMock(LeadEventLog::class);
        $log->method('getLead')
            ->willReturn($lead);
        $log->method('setIsScheduled')
            ->willReturn($log);
        $log->method('getEvent')
            ->willReturn($event);
        $log->method('getId')
            ->willReturn(1);

        $logs = new ArrayCollection(
            [
                1 => $log,
            ]
        );

        $config = new ActionAccessor(
            [
                'label'                  => 'mautic.campaign.event.jump_to_event',
                'description'            => 'mautic.campaign.event.jump_to_event_descr',
                'formType'               => CampaignEventJumpToEventType::class,
                'template'               => '@MauticCampaign/Event/jump.html.twig',
                'batchEventName'         => CampaignEvents::ON_EVENT_JUMP_TO_EVENT,
                'connectionRestrictions' => [
                    'target' => [
                        Event::TYPE_DECISION  => ['none'],
                        Event::TYPE_ACTION    => ['none'],
                        Event::TYPE_CONDITION => ['none'],
                    ],
                ],
            ]
        );

        $pendingEvent = new PendingEvent($config, $event, $logs);

        $this->eventRepository->method('getEntities')
            ->willReturn([]);

        $eventScheduler = $this->createStub(EventScheduler::class);

        $subscriber = new CampaignActionJumpToEventSubscriber(
            $this->eventRepository,
            $this->getEventExecutioner(),
            $this->createStub(Translator::class),
            $this->leadRepository,
            $eventScheduler
        );
        $subscriber->onJumpToEvent($pendingEvent);

        $this->assertCount(1, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }
}
