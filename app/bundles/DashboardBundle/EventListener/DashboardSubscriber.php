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
use Mautic\DashboardBundle\Event\WidgetTypeListEvent;
use Mautic\DashboardBundle\Event\WidgetFormEvent;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\DashboardBundle\EventListener
 */
class DashboardSubscriber extends CommonSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'others';

    /**
     * Define the widget(s)
     *
     * @var array
     */
    protected $types = array();

    /**
     * Define permissions to see those widgets
     *
     * @var array
     */
    protected $permissions = array();

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE => array('onWidgetListGenerate', 0),
            DashboardEvents::DASHBOARD_ON_MODULE_FORM_GENERATE => array('onWidgetFormGenerate', 0),
            DashboardEvents::DASHBOARD_ON_MODULE_DETAIL_GENERATE => array('onWidgetDetailGenerate', 0),
        );
    }

    /**
     * Adds widget new widget types to the list of available widget types 
     *
     * @param WidgetTypeListEvent $event
     *
     * @return void
     */
    public function onWidgetListGenerate(WidgetTypeListEvent $event)
    {
        if ($this->permissions && !$event->hasPermissions($this->permissions)) return;

        $widgetTypes = array_keys($this->types);

        foreach ($widgetTypes as $type) {
            $event->addType($type, $this->bundle);
        }
    }

    /**
     * Set a widget edit form when needed 
     *
     * @param WidgetFormEvent $event
     *
     * @return void
     */
    public function onWidgetFormGenerate(WidgetFormEvent $event)
    {
        if (isset($this->types[$event->getType()])) {
            $event->setForm($this->types[$event->getType()]);
            $event->stopPropagation();
        }
    }

    /**
     * Set a widget detail when needed 
     *
     * @param WidgetDetailEvent $event
     *
     * @return void
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event)
    {
    }

    /**
     * Set a widget detail when needed 
     *
     * @param WidgetDetailEvent $event
     *
     * @return void
     */
    public function checkPermissions(WidgetDetailEvent $event)
    {
        $widgetTypes = array_keys($this->types);
        if ($this->permissions && !$event->hasPermissions($this->permissions) && in_array($event->getType(), $widgetTypes)) {
            $translator = $event->getTranslator();
            $event->setErrorMessage($translator->trans('mautic.dashboard.missing.permission', array('%section%' => $this->bundle)));
            $event->stopPropagation();
            return;
        }
    }
}
