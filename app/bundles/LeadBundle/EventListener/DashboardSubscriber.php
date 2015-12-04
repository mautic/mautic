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
use Mautic\DashboardBundle\Event\ModuleDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the module(s)
     *
     * @var string
     */
    protected $bundle = 'lead';

    /**
     * Define the module(s)
     *
     * @var string
     */
    protected $types = array(
        'created.leads.in.time' => array(
            'formAlias' => 'lead_dashboard_leads_in_time_module',
            'template' => 'MauticDashboardBundle:Module:module.html.php',
            'callback' => array(
                'model' => 'lead',
                'method' => 'getLeadsLineChartData'
            )
        )
    );

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
