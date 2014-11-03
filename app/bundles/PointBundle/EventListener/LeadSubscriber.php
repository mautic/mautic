<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            LeadEvents::LEAD_POINTS_CHANGE => array('onLeadPointsChange', 0)
        );
    }

    /**
     * Trigger applicable events for the lead
     *
     * @param PointsChangeEvent $event
     */
    public function onLeadPointsChange(PointsChangeEvent $event)
    {
        /** @var \Mautic\PointBundle\Model\TriggerModel */
        $model = $this->factory->getModel('point.trigger');
        $model->triggerEvents($event->getLead());
    }
}
