<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\EventListener;

use Mautic\MapperBundle\MapperEvents;
use Mautic\MapperBundle\Event\MapperDashboardEvent;

/**
 * Class MapperListener
 *
 * @package Mautic\MapperBundle\EventListener
 */
class MapperListener extends MapperSubscriber
{
    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            MapperEvents::FETCH_ICONS           => array('onFetchIcons', 9999)
        );
    }

    /**
     * @param MenuEvent $event
     */
    public function onFetchIcons (MapperDashboardEvent $event)
    {
        $this->buildIcons($event);
    }
}