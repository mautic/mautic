<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0),
            LeadEvents::LEAD_POST_MERGE      => array('onLeadMerge', 0)
        );
    }

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKeySent  = 'email.sent';
        $eventTypeNameSent = $this->translator->trans('mautic.email.sent');
        $event->addEventType($eventTypeKeySent, $eventTypeNameSent);

        $eventTypeKeyRead  = 'email.read';
        $eventTypeNameRead = $this->translator->trans('mautic.email.read');
        $event->addEventType($eventTypeKeyRead, $eventTypeNameRead);

        // Decide if those events are filtered
        $filters = $event->getEventFilters();

        $lead    = $event->getLead();
        $options = array('ipIds' => array(), 'filters' => $filters);

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        /*
        foreach ($lead->getIpAddresses() as $ip) {
            $options['ipIds'][] = $ip->getId();
        }
        */

        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepository */
        $statRepository = $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');

        $stats = $statRepository->getLeadStats($lead->getId(), $options);

        // Add the events to the event array
        foreach ($stats as $stat) {
            if ($stat['dateRead'] && $event->isApplicable($eventTypeKeyRead, true)) {
                $event->addEvent(
                    array(
                        'event'           => $eventTypeKeyRead,
                        'eventLabel'      => $eventTypeNameRead,
                        'timestamp'       => $stat['dateRead'],
                        'extra'           => array(
                            'stat' => $stat,
                            'type' => 'read'
                        ),
                        'contentTemplate' => 'MauticEmailBundle:SubscribedEvents\Timeline:index.html.php',
                        'icon'            => 'fa-envelope-o'
                    )
                );
            }

            // Email read
            if ($stat['dateSent'] && $event->isApplicable($eventTypeKeySent)) {
                $event->addEvent(
                    array(
                        'event'           => $eventTypeKeySent,
                        'eventLabel'      => $eventTypeNameSent,
                        'timestamp'       => $stat['dateSent'],
                        'extra'           => array(
                            'stat' => $stat,
                            'type' => 'sent'
                        ),
                        'contentTemplate' => 'MauticEmailBundle:SubscribedEvents\Timeline:index.html.php',
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
        $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }
}