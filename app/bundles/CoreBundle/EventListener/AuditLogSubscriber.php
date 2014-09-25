<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class AuditLogSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class AuditLogSubscriber extends CommonSubscriber
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
        $lead = $event->getLead();

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
            $event->addEvent(array(
                'event'     => 'lead.' . $row->getAction(),
                'timestamp' => $row->getDateAdded(),
                'extra'     => array(
                    'page_id' => $row->getDetails(),
                    'editor'  => $row->getUserName()
                )
            ));
        }
    }
}
