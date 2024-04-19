<?php

namespace Mautic\DashboardBundle;

/**
 * Events available for DashboardBundle.
 */
final class DashboardEvents
{
    /**
     * The mautic.dashboard_on_widget_list_generate event is dispatched when generating a list of available widget types.
     *
     * The event listener receives a
     * Mautic\DashboardBundle\Event\WidgetTypeListEvent instance.
     *
     * @var string
     */
    public const DASHBOARD_ON_MODULE_LIST_GENERATE = 'mautic.dashboard_on_widget_list_generate';

    /**
     * The mautic.dashboard_on_widget_form_generate event is dispatched when generating the form of a widget type.
     *
     * The event listener receives a
     * Mautic\DashboardBundle\Event\WidgetFormEvent instance.
     *
     * @var string
     */
    public const DASHBOARD_ON_MODULE_FORM_GENERATE = 'mautic.dashboard_on_widget_form_generate';

    /**
     * The mautic.dashboard_on_widget_detail_generate event is dispatched when generating the detail of a widget type.
     *
     * The event listener receives a
     * Mautic\DashboardBundle\Event\WidgetDetailEvent instance.
     *
     * @var string
     */
    public const DASHBOARD_ON_MODULE_DETAIL_GENERATE = 'mautic.dashboard_on_widget_detail_generate';

    /**
     * The mautic.dashboard_on_widget_detail_pre_load event is dispatched before detail of a widget type is generate.
     *
     * The event listener receives a
     * Mautic\DashboardBundle\Event\WidgetDetailEvent instance.
     *
     * @var string
     */
    public const DASHBOARD_ON_MODULE_DETAIL_PRE_LOAD = 'mautic.dashboard_on_widget_detail_pre_load';
}
