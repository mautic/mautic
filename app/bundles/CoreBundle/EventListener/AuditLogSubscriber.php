<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class AuditLogSubscriber
 */
class AuditLogSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0)
        );
    }

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     *
     * @return void
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $lead    = $event->getLead();

        /** @var \Mautic\CoreBundle\Model\AuditLogModel $model */
        $model = $this->factory->getModel('core.auditLog');
        $rows  = $model->getEntities(array(
            'filter' => array(
                'force' => array(
                    array(
                        'column' => 'e.bundle',
                        'expr'   => 'eq',
                        'value'  => 'lead'
                    ),
                    array(
                        'column' => 'e.object',
                        'expr'   => 'eq',
                        'value'  => 'lead'
                    ),
                    array(
                        'column' => 'e.action',
                        'expr'   => 'neq',
                        'value'  => 'update'
                    ),
                    array(
                        'column' => 'e.objectId',
                        'expr'   => 'eq',
                        'value'  => $lead->getId()
                    )
                )
            )
        ));

        $filters = $event->getEventFilters();

        // Add the entries to the event array
        /** @var \Mautic\CoreBundle\Entity\AuditLog $row */
        $IpAddresses = $lead->getIpAddresses();
        foreach ($rows as $row) {
            $action       = $row->getAction();
            $eventTypeKey = 'lead.' . $action;

            //don't include if type is not applicable or if there is a search string as there is nothing to search for this
            if (!$event->isApplicable($eventTypeKey) || !empty($filters['search'])) {
                continue;
            }

            $eventLabel = $this->translator->trans('mautic.lead.event.'. $row->getAction());

            $details = $row->getDetails();

            // Guess the IP address
            if (is_string($details)) {
                $ipAddress = $details;
            }elseif (isset($details['ipAddresses'][1])) {
                $ipAddress = $details['ipAddresses'][1];
            } elseif (isset($details[1])) {
                $ipAddress = $details[1];
            } else {
                $row->getIpAddress();
            }

            $event->addEvent(array(
                'event'      => $eventTypeKey,
                'eventLabel' => $eventLabel,
                'timestamp' => $row->getDateAdded(),
                'extra'     => array(
                    'details'   => $details,
                    'editor'    => $row->getUserName(),
                    'ipDetails' => ($eventTypeKey == 'lead.ipadded') ? $IpAddresses[$ipAddress] : array()
                ),
                'contentTemplate' => 'MauticLeadBundle:SubscribedEvents\Timeline:' . $action . '.html.php'
            ));
        }
    }
}
