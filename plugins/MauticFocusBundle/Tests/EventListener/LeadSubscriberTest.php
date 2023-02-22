<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\EventListener;

use DateTime;
use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticFocusBundle\Entity\StatRepository;
use MauticPlugin\MauticFocusBundle\EventListener\LeadSubscriber;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouterInterface;

class LeadSubscriberTest extends CommonMocks
{
    /**
     * @var Translator|MockObject
     */
    private $translator;

    /**
     * @var RouterInterface|MockObject
     */
    private $router;

    /**
     * @var FocusModel
     */
    private $focusModel;

    /**
     * @var StatRepository|(StatRepository&MockObject)|MockObject
     */
    private $statRepository;

    protected function setUp(): void
    {
        $this->translator     = $this->createMock(Translator::class);
        $this->router         = $this->createMock(RouterInterface::class);
        $this->focusModel     = $this->createMock(FocusModel::class);
        $this->statRepository = $this->createMock(StatRepository::class);
    }

    public function testShowFocusItem()
    {
        $date      = new DateTime();
        $focusName = 'test focus';

        $stats = [
            'result'=> [
                [
                    'id'        => 1,
                    'type'      => 'view',
                    'dateAdded' => $date,
                    'focus'     => [
                        'id'   => 1,
                        'name' => $focusName,
                    ],
                ],
            ],
            'total'=> 1,
        ];

        $this->statRepository->method('getStatsViewByLead')->willReturn($stats);
        $this->focusModel->method('getStatRepository')->willReturn($this->statRepository);

        /**
         * @todo dostosuj do FI
         */
        $eventTypeKey       = 'focus.view';
        $eventTypeName      = 'Focus view';
        $eventTypeClickName = 'Focus click';

        $this->translator->expects($this->any())
            ->method('trans')
            ->withConsecutive(['mautic.focus.event.view'], ['mautic.focus.event.click'])
            ->willReturnOnConsecutiveCalls($eventTypeName, $eventTypeClickName);

        $lead = new Lead();
        $lead->setId(1);

        $leadEventLogId = 1;

        $timelineEvent = [
            'event'      => $eventTypeKey,
            'eventId'    => $eventTypeKey.'.'.$leadEventLogId,

            'eventLabel' => [
                'label' => $focusName,
                'href'  => null,
            ],
            'eventType'       => $eventTypeName,
            'timestamp'       => $date,
            'contentTemplate' => 'MauticFocusBundle:SubscribedEvents\Timeline:index.html.php',
            'icon'            => 'fa-search',
            'contactId'       => $leadEventLogId,
        ];

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
}
