<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\DashboardBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\ModuleTypeListEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\DashboardBundle\EventListener
 */
class DashboardSubscriber extends CommonSubscriber
{
    protected $bundle = 'others';
    protected $types = array();

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE => array('onModuleListGenerate', 0)
        );
    }

    /**
     * Adds module new module types to the list of available module types 
     *
     * @param ModuleTypeListEvent $event
     *
     * @return void
     */
    public function onModuleListGenerate(ModuleTypeListEvent $event)
    {
        foreach ($this->types as $type) {
            $event->addType($type, $this->bundle);
        }
    }
}