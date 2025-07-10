<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\EventListener;

use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Entity\StatRepository;
use MauticPlugin\MauticFocusBundle\EventListener\LeadSubscriber;
use MauticPlugin\MauticFocusBundle\FocusEventTypes;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouterInterface;

class LeadSubscriberTest extends CommonMocks
{
    /**
     * @var Translator|MockObject
     */
    private MockObject $translator;

    /**
     * @var RouterInterface|MockObject
     */
    private MockObject $router;

    /**
     * @var FocusModel|(FocusModel&MockObject)|MockObject
     */
    private MockObject $focusModel;

    /**
     * @var StatRepository|(StatRepository&MockObject)|MockObject
     */
    private MockObject $statRepository;

    /**
     * @var string
     */
    private const EVENT_TYPE_VIEW_NAME = 'Focus view';

    /**
     * @var string
     */
    private const EVENT_TYPE_CLICK_NAME = 'Focus click';

    /**
     * @var string
     */
    private const FOCUS_NAME = 'test Focus Item';

    protected function setUp(): void
    {
        $this->translator     = $this->createMock(Translator::class);
        $this->router         = $this->createMock(RouterInterface::class);
        $this->focusModel     = $this->createMock(FocusModel::class);
        $this->statRepository = $this->createMock(StatRepository::class);

        $this->translator->expects($this->any())
            ->method('trans')
            ->withConsecutive(['mautic.focus.event.view'], ['mautic.focus.event.click'])
            ->willReturnOnConsecutiveCalls(self::EVENT_TYPE_VIEW_NAME, self::EVENT_TYPE_CLICK_NAME);
    }

    /**
     * Make sure that on timeline entry is created for a lead
     * that was displayed Focus Item.
     */
    public function testShowFocusItem(): void
    {
        $lead = $this->getLead();
        $date = new \DateTime();

        $this->mockFocusModelGetStatsByLead(Stat::TYPE_NOTIFICATION, self::FOCUS_NAME, 'getStatsViewByLead', $date);

        $timelineEvent = $this->getTimelineEvent(
            FocusEventTypes::FOCUS_ON_VIEW, self::EVENT_TYPE_VIEW_NAME, self::FOCUS_NAME, $date, $lead
        );

        $leadEvent  = new LeadTimelineEvent($lead);
        $subscriber = new LeadSubscriber(
            $this->translator,
            $this->router,
            $this->focusModel
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($leadEvent, LeadEvents::TIMELINE_ON_GENERATE);

        $this->assertSame([$timelineEvent], $leadEvent->getEvents());
    }

    public function testShowFocusItemWhenNoLead(): void
    {
        $date = new \DateTime();

        $this->mockFocusModelGetStatsByLead(Stat::TYPE_NOTIFICATION, self::FOCUS_NAME, 'getStatsViewByLead', $date);

        $timelineEvent = $this->getTimelineEvent(
            FocusEventTypes::FOCUS_ON_VIEW, self::EVENT_TYPE_VIEW_NAME, self::FOCUS_NAME, $date
        );

        $leadEvent  = new LeadTimelineEvent();
        $subscriber = new LeadSubscriber(
            $this->translator,
            $this->router,
            $this->focusModel
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($leadEvent, LeadEvents::TIMELINE_ON_GENERATE);

        $this->assertSame([$timelineEvent], $leadEvent->getEvents());
    }

    /**
     * Make sure that on timeline entry is created for a lead
     * that was clicked Focus Item.
     */
    public function testClickFocusItem(): void
    {
        $lead = $this->getLead();
        $date = new \DateTime();

        $this->mockFocusModelGetStatsByLead(Stat::TYPE_CLICK, self::FOCUS_NAME, 'getStatsClickByLead', $date);

        $timelineEvent = $this->getTimelineEvent(
            FocusEventTypes::FOCUS_ON_CLICK, self::EVENT_TYPE_CLICK_NAME, self::FOCUS_NAME, $date, $lead
        );

        $leadEvent  = new LeadTimelineEvent($lead);
        $subscriber = new LeadSubscriber(
            $this->translator,
            $this->router,
            $this->focusModel
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($leadEvent, LeadEvents::TIMELINE_ON_GENERATE);

        $this->assertSame([$timelineEvent], $leadEvent->getEvents());
    }

    public function testClickFocusItemWhenNoLead(): void
    {
        $date = new \DateTime();

        $this->mockFocusModelGetStatsByLead(Stat::TYPE_CLICK, self::FOCUS_NAME, 'getStatsClickByLead', $date);

        $timelineEvent = $this->getTimelineEvent(
            FocusEventTypes::FOCUS_ON_CLICK, self::EVENT_TYPE_CLICK_NAME, self::FOCUS_NAME, $date
        );

        $leadEvent  = new LeadTimelineEvent();
        $subscriber = new LeadSubscriber(
            $this->translator,
            $this->router,
            $this->focusModel
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($leadEvent, LeadEvents::TIMELINE_ON_GENERATE);

        $this->assertSame([$timelineEvent], $leadEvent->getEvents());
    }

    private function mockFocusModelGetStatsByLead(string $statType, string $focusName, string $method, \DateTime $date): void
    {
        $stats = [
            'results'=> [
                [
                    'id'         => 1,
                    'type'       => $statType,
                    'date_added' => $date,
                    'focus_id'   => 1,
                    'focus_name' => $focusName,
                ],
            ],
            'total'=> 1,
        ];

        $this->statRepository->method($method)->willReturn($stats);
        $this->focusModel->method('getStatRepository')->willReturn($this->statRepository);
    }

    private function getLead(): Lead
    {
        $lead = new Lead();
        $lead->setId(1);

        return $lead;
    }

    /**
     * @return array<string, mixed>
     */
    private function getTimelineEvent(string $eventType, string $eventTypeName, string $focusName, \DateTime $date, ?Lead $lead=null): array
    {
        $leadEventLogId = 1;

        return [
            'event'      => $eventType,
            'eventId'    => $eventType.'.'.$leadEventLogId,
            'eventLabel' => [
                'label' => $focusName,
                'href'  => '',
            ],
            'eventType'       => $eventTypeName,
            'timestamp'       => $date,
            'icon'            => 'ri-search-line',
            'contactId'       => $lead?->getId(),
        ];
    }
}
