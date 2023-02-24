<?php

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CoreBundle\EventListener\ChannelTrait;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\FocusEventTypes;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    use ChannelTrait;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /*
     * @var FocusModel
     */
    private FocusModel $focusModel;

    public function __construct(
        Translator $translator,
        RouterInterface $router,
        FocusModel $focusModel
    ) {
        $this->translator     = $translator;
        $this->router         = $router;
        $this->focusModel     = $focusModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE      => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $eventViewTypeName = $this->translator->trans('mautic.focus.event.view');
        $event->addEventType(FocusEventTypes::FOCUS_ON_VIEW, $eventViewTypeName);
        $eventViewApplicable = $event->isApplicable(FocusEventTypes::FOCUS_ON_VIEW);

        $eventClickTypeName = $this->translator->trans('mautic.focus.event.click');
        $event->addEventType(FocusEventTypes::FOCUS_ON_CLICK, $eventClickTypeName);
        $eventClickApplicable = $event->isApplicable(FocusEventTypes::FOCUS_ON_CLICK);

        $event->addSerializerGroup('focusList');

        $contactId        = $event->getLead()->getId();
        $statsViewsByLead = $this->focusModel->getStatRepository()->getStatsViewByLead($contactId, $event->getQueryOptions());

        if (!$event->isEngagementCount()) {
            $template = 'MauticFocusBundle:SubscribedEvents\Timeline:index.html.php';
            $icon     = 'fa-search';

            $counter = [Stat::TYPE_NOTIFICATION=>0, Stat::TYPE_CLICK=>0];
            // Add the view to the event array
            foreach ($statsViewsByLead['result'] as $statsView) {
                if (((Stat::TYPE_CLICK == $statsView['type']) && $eventClickApplicable)
                    || ((Stat::TYPE_NOTIFICATION == $statsView['type']) && $eventViewApplicable)) {
                    ++$counter[$statsView['type']];

                    $eventLabel = [
                        'label' => $statsView['focus']['name'],
                        'href'  => $this->router->generate('mautic_focus_action', ['objectAction' => 'view', 'objectId' => $statsView['focus']['id']]),
                    ];

                    $eventType = (Stat::TYPE_NOTIFICATION == $statsView['type']) ? FocusEventTypes::FOCUS_ON_VIEW : FocusEventTypes::FOCUS_ON_CLICK;

                    $event->addEvent(
                        [
                            'event'           => $eventType,
                            'eventId'         => $eventType.'.'.$statsView['id'],
                            'eventLabel'      => $eventLabel,
                            'eventType'       => (Stat::TYPE_NOTIFICATION == $statsView['type']) ? $eventViewTypeName : $eventClickTypeName,
                            'timestamp'       => $statsView['dateAdded'],
                            'contentTemplate' => $template,
                            'icon'            => $icon,
                            'contactId'       => $contactId,
                        ]
                    );
                }
            }

            // Add to counter view
            $event->addToCounter(FocusEventTypes::FOCUS_ON_VIEW, $counter[Stat::TYPE_NOTIFICATION]);
            // Add to counter click
            $event->addToCounter(FocusEventTypes::FOCUS_ON_CLICK, $counter[Stat::TYPE_CLICK]);
        }
    }
}
