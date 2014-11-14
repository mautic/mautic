<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
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
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0)
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
        $eventTypeKeySent = 'email.sent';
        $eventTypeNameSent = $this->translator->trans('mautic.email.event.sent');
        $event->addEventType($eventTypeKeySent, $eventTypeNameSent);

        $eventTypeKeyRead = 'email.read';
        $eventTypeNameRead = $this->translator->trans('mautic.email.event.read');
        $event->addEventType($eventTypeKeyRead, $eventTypeNameRead);

        // Decide if those events are filtered
        $filter = $event->getEventFilter();
        $loadAllEvents = !isset($filter[0]);
        $sentEventFilterExists = in_array($eventTypeKeySent, $filter);
        $readEventFilterExists = in_array($eventTypeKeyRead, $filter);

        if (!$loadAllEvents && !($sentEventFilterExists || $readEventFilterExists)) {
            return;
        }

        $lead    = $event->getLead();
        $options = array('ipIds' => array(), 'filters' => $filter);

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        foreach ($lead->getIpAddresses() as $ip) {
            $options['ipIds'][] = $ip->getId();
        }

        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepository */
        $statRepository = $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');

        $stats = $statRepository->getLeadStats($lead->getId(), $options);

        // Add the events to the event array
        foreach ($stats as $stat) {
            // Email Sent
            if (($loadAllEvents || $sentEventFilterExists) && $stat['dateSent']) {
                $event->addEvent(array(
                    'event'     => $eventTypeKeySent,
                    'eventLabel' => $eventTypeNameSent,
                    'timestamp' => $stat['dateSent'],
                    'extra'     => array(
                        'stats' => $stat
                    ),
                    'contentTemplate' => 'MauticEmailBundle:Timeline:index.html.php'
                ));
            }

            // Email read
            if (($loadAllEvents || $readEventFilterExists) && $stat['dateRead']) {
                $event->addEvent(array(
                    'event'     => $eventTypeKeyRead,
                    'eventLabel' => $eventTypeNameRead,
                    'timestamp' => $stat['dateRead'],
                    'extra'     => array(
                        'stats' => $stat
                    ),
                    'contentTemplate' => 'MauticEmailBundle:Timeline:index.html.php'
                ));
            }
        }
    }
}