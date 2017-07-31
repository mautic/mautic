<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addEmailEvents($event, 'read');
        $this->addEmailEvents($event, 'sent');
        $this->addEmailEvents($event, 'failed');
    }

    /**
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->em->getRepository('MauticEmailBundle:Stat')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }

    /**
     * @param LeadTimelineEvent $event
     * @param                   $state
     */
    protected function addEmailEvents(LeadTimelineEvent $event, $state)
    {
        // Set available event types
        $eventTypeKey  = 'email.'.$state;
        $eventTypeName = $this->translator->trans('mautic.email.'.$state);
        $event->addEventType($eventTypeKey, $eventTypeName);

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepository */
        $statRepository        = $this->em->getRepository('MauticEmailBundle:Stat');
        $queryOptions          = $event->getQueryOptions();
        $queryOptions['state'] = $state;
        $stats                 = $statRepository->getLeadStats($event->getLeadId(), $queryOptions);

        // Add total to counter
        $event->addToCounter($eventTypeKey, $stats);

        if (!$event->isEngagementCount()) {
            // Add the events to the event array
            foreach ($stats['results'] as $stat) {
                if (!empty($stat['email_name'])) {
                    $label = $stat['email_name'];
                } elseif (!empty($stat['storedSubject'])) {
                    $label = $this->translator->trans('mautic.email.timeline.event.custom_email').': '.$stat['storedSubject'];
                } else {
                    $label = $this->translator->trans('mautic.email.timeline.event.custom_email');
                }

                if (!empty($stat['idHash'])) {
                    $eventName = [
                        'label'      => $label,
                        'href'       => $this->router->generate('mautic_email_webview', ['idHash' => $stat['idHash']]),
                        'isExternal' => true,
                    ];
                } else {
                    $eventName = $label;
                }
                if ('failed' == $state or 'sent' == $state) { //this is to get the correct column for date dateSent
                    $dateSent = 'sent';
                } else {
                    $dateSent = 'read';
                }
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventLabel' => $eventName,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $stat['date'.ucfirst($dateSent)],
                        'dateSent'   => $stat['dateSent'],
                        'extra'      => [
                            'stat' => $stat,
                            'type' => $state,
                        ],
                        'contentTemplate' => 'MauticEmailBundle:SubscribedEvents\Timeline:index.html.php',
                        'icon'            => ($state == 'read') ? 'fa-envelope-o' : 'fa-envelope',
                    ]
                );
            }
        }
    }
}
