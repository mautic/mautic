<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\LeadBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\ModuleTypeListEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    protected $bundle = 'lead';
    protected $types = array(
        'created.leads.in.time' => array('formAlias' => 'lead_dashboard_leads_in_time_module')
    );

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE => array('onModuleListGenerate', 0),
            DashboardEvents::DASHBOARD_ON_MODULE_FORM_GENERATE => array('onModuleFormGenerate', 0),
        );
    }
}