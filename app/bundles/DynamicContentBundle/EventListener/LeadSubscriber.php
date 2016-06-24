<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\DynamicContent\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0]
        ];
    }

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKeySent  = 'dynamic.content.sent';
        $eventTypeNameSent = $this->translator->trans('mautic.dynamic.content.sent');
        $event->addEventType($eventTypeKeySent, $eventTypeNameSent);

        $lead = $event->getLead();

        /** @var \Mautic\DynamicContentBundle\Entity\StatRepository $statRepository */
        $statRepository = $this->factory->getEntityManager()->getRepository('MauticDynamicContentBundle:Stat');

        $stats = $statRepository->getLeadStats($lead->getId(), ['filters' => $event->getEventFilters()]);

        // Add the events to the event array
        foreach ($stats as $stat) {
            // Email sent
            if ($stat['dateSent'] && $event->isApplicable($eventTypeKeySent)) {
                $event->addEvent(
                    array(
                        'event'           => $eventTypeKeySent,
                        'eventLabel'      => $eventTypeNameSent,
                        'timestamp'       => $stat['dateSent'],
                        'extra'           => [
                            'stat' => $stat,
                            'type' => 'sent'
                        ],
                        'contentTemplate' => 'MauticDynamicContentBundle:SubscribedEvents\Timeline:index.html.php',
                        'icon'            => 'fa-envelope'
                    )
                );
            }
        }
    }

    /**
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->factory->getEntityManager()->getRepository('MauticDynamicContentBundle:Stat')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }
}