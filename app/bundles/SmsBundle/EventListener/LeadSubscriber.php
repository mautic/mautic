<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(TranslatorInterface $translator, RouterInterface $router, EntityManager $em)
    {
        $this->translator = $translator;
        $this->router     = $router;
        $this->em         = $em;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addSmsEvents($event, 'sent');
        $this->addSmsEvents($event, 'failed');
    }

    protected function addSmsEvents(LeadTimelineEvent $event, $state)
    {
        // Set available event types
        $eventTypeKey  = 'sms.'.$state;
        $eventTypeName = $this->translator->trans('mautic.sms.timeline.status.'.$state);
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('smsList');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        /** @var \Mautic\SmsBundle\Entity\StatRepository $statRepository */
        $statRepository        = $this->em->getRepository('MauticSmsBundle:Stat');
        $queryOptions          = $event->getQueryOptions();
        $queryOptions['state'] = $state;
        $stats                 = $statRepository->getLeadStats($event->getLeadId(), $queryOptions);

        // Add total to counter
        $event->addToCounter($eventTypeKey, $stats);

        if (!$event->isEngagementCount()) {
            // Add the events to the event array
            foreach ($stats['results'] as $stat) {
                if (!empty($stat['sms_name'])) {
                    $label = $stat['sms_name'];
                } else {
                    $label = $this->translator->trans('mautic.sms.timeline.event.custom_sms');
                }

                $eventName = [
                        'label'      => $label,
                        'href'       => $this->router->generate('mautic_sms_action', ['objectAction'=>'view', 'objectId' => $stat['sms_id']]),
                    ];
                if ('failed' == $state or 'sent' == $state) { //this is to get the correct column for date dateSent
                    $dateSent = 'sent';
                }

                $contactId = $stat['lead_id'];
                unset($stat['lead_id']);
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$stat['id'],
                        'eventLabel' => $eventName,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $stat['date'.ucfirst($dateSent)],
                        'extra'      => [
                            'stat'   => $stat,
                            'type'   => $state,
                        ],
                        'contentTemplate' => 'MauticSmsBundle:SubscribedEvents\Timeline:index.html.php',
                        'icon'            => ('read' == $state) ? 'fa-message-o' : 'fa-commenting',
                        'contactId'       => $contactId,
                    ]
                );
            }
        }
    }
}
