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
use Mautic\DashboardBundle\Event\ModuleFormEvent;
use Mautic\DashboardBundle\Event\ModuleDetailEvent;
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
            DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE => array('onModuleListGenerate', 0),
            DashboardEvents::DASHBOARD_ON_MODULE_FORM_GENERATE => array('onModuleFormGenerate', 0),
            DashboardEvents::DASHBOARD_ON_MODULE_DETAIL_GENERATE => array('onModuleDetailGenerate', 0),
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
        $moduleTypes = array_keys($this->types);

        foreach ($moduleTypes as $type) {
            $event->addType($type, $this->bundle);
        }
    }

    /**
     * Set a module edit form when needed 
     *
     * @param ModuleFormEvent $event
     *
     * @return void
     */
    public function onModuleFormGenerate(ModuleFormEvent $event)
    {
        if (isset($this->types[$event->getType()])) {
            $event->setForm($this->types[$event->getType()]);
        }
    }

    /**
     * Set a module detail when needed 
     *
     * @param ModuleDetailEvent $event
     *
     * @return void
     */
    public function onModuleDetailGenerate(ModuleDetailEvent $event)
    {
    }
}
