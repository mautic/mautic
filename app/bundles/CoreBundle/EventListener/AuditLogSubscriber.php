<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

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
        $lead = $event->getLead();

        $filter = $event->getEventFilter();
        $loadAllEvents = !isset($filter[0]);

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
                        'column' => 'e.objectId',
                        'expr'   => 'eq',
                        'value'  => $lead->getId()
                    )
                )
            )
        ));

        // Add the entries to the event array
        /** @var \Mautic\CoreBundle\Entity\AuditLog $row */
        foreach ($rows as $row) {
            $eventTypeKey      = 'lead.' . $row->getAction();
            $eventFilterExists = in_array($eventTypeKey, $filter);
            $eventLabel        = $this->translator->trans('mautic.lead.event.'. $row->getAction());

            if (!$loadAllEvents && !$eventFilterExists) {
                continue;
            }

            $event->addEvent(array(
                'event'      => $eventTypeKey,
                'eventLabel' => $eventLabel,
                'timestamp' => $row->getDateAdded(),
                'extra'     => array(
                    'details' => $row->getDetails(),
                    'editor'  => $row->getUserName()
                ),
                'contentTemplate' => 'MauticLeadBundle:Timeline:index.html.php'
            ));
        }
    }
}
