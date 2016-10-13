<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle;

/**
 * Class LeadEvents
 * Events available for DashboardBundle.
 */
final class DashboardEvents
{
    /**
     * The mautic.dashboard_on_widget_list_generate event is dispatched when generating a list of available widget types.
     *
     * The event listener receives a
     * Mautic\DashbardBundle\Event\WidgetTypeListEvent instance.
     *
     * @var string
     */
    const DASHBOARD_ON_MODULE_LIST_GENERATE = 'mautic.dashboard_on_widget_list_generate';

    /**
     * The mautic.dashboard_on_widget_form_generate event is dispatched when generating the form of a widget type.
     *
     * The event listener receives a
     * Mautic\DashbardBundle\Event\WidgetFormEvent instance.
     *
     * @var string
     */
    const DASHBOARD_ON_MODULE_FORM_GENERATE = 'mautic.dashboard_on_widget_form_generate';

    /**
     * The mautic.dashboard_on_widget_detail_generate event is dispatched when generating the detail of a widget type.
     *
     * The event listener receives a
     * Mautic\DashbardBundle\Event\WidgetDetailEvent instance.
     *
     * @var string
     */
    const DASHBOARD_ON_MODULE_DETAIL_GENERATE = 'mautic.dashboard_on_widget_detail_generate';
}
