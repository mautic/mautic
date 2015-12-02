<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle;

/**
 * Class LeadEvents
 * Events available for DashboardBundle
 *
 * @package Mautic\DashboardBundle
 */
final class DashboardEvents
{
    /**
     * The mautic.dashboard_on_module_list_generate event is dispatched when generating a list of available module types
     *
     * The event listener receives a
     * Mautic\DashbardBundle\Event\ModuleListEvent instance.
     *
     * @var string
     */
    const DASHBOARD_ON_MODULE_LIST_GENERATE = 'mautic.dashboard_on_module_list_generate';

    /**
     * The mautic.dashboard_on_module_form_generate event is dispatched when generating the form of a module type
     *
     * The event listener receives a
     * Mautic\DashbardBundle\Event\ModuleListEvent instance.
     *
     * @var string
     */
    const DASHBOARD_ON_MODULE_FORM_GENERATE = 'mautic.dashboard_on_module_form_generate';
}
