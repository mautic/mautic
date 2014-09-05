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
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\PointBundle\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            LeadEvents::LEAD_SCORE_CHANGE => array('onLeadScoreChange', 0)
        );
    }

    /**
     * Trigger applicable events for the lead
     *
     * @param LeadEvent $event
     */
    public function onLeadScoreChange(LeadEvent $event)
    {
        /** @var \Mautic\PointBundle\Model\TriggerModel */
        $model = $this->factory->getModel('point.trigger');
        $model->triggerEvents($event->getLead());
    }
}