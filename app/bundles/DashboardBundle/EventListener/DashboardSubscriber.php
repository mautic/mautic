<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\Event\WidgetFormEvent;
use Mautic\DashboardBundle\Event\WidgetTypeListEvent;

/**
 * Class DashboardSubscriber.
 */
class DashboardSubscriber extends CommonSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s).
     *
     * @var string
     */
    protected $bundle = 'others';

    /**
     * Define the widget(s).
     *
     * @var array
     */
    protected $types = [];

    /**
     * Define permissions to see those widgets.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE   => ['onWidgetListGenerate', 0],
            DashboardEvents::DASHBOARD_ON_MODULE_FORM_GENERATE   => ['onWidgetFormGenerate', 0],
            DashboardEvents::DASHBOARD_ON_MODULE_DETAIL_GENERATE => ['onWidgetDetailGenerate', 0],
        ];
    }

    /**
     * Adds widget new widget types to the list of available widget types.
     *
     * @param WidgetTypeListEvent $event
     */
    public function onWidgetListGenerate(WidgetTypeListEvent $event)
    {
        if ($this->permissions && !$event->hasPermissions($this->permissions)) {
            return;
        }

        $widgetTypes = array_keys($this->types);

        foreach ($widgetTypes as $type) {
            $event->addType($type, $this->bundle);
        }
    }

    /**
     * Set a widget edit form when needed.
     *
     * @param WidgetFormEvent $event
     */
    public function onWidgetFormGenerate(WidgetFormEvent $event)
    {
        if (isset($this->types[$event->getType()])) {
            $event->setForm($this->types[$event->getType()]);
            $event->stopPropagation();
        }
    }

    /**
     * Set a widget detail when needed.
     *
     * @param WidgetDetailEvent $event
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event)
    {
    }

    /**
     * Set a widget detail when needed.
     *
     * @param WidgetDetailEvent $event
     */
    public function checkPermissions(WidgetDetailEvent $event)
    {
        $widgetTypes = array_keys($this->types);
        if ($this->permissions && !$event->hasPermissions($this->permissions) && in_array($event->getType(), $widgetTypes)) {
            $translator = $event->getTranslator();
            $event->setErrorMessage($translator->trans('mautic.dashboard.missing.permission', ['%section%' => $this->bundle]));
            $event->stopPropagation();

            return;
        }
    }
}
