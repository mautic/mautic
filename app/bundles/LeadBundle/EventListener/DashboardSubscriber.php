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
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
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
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'lead';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'created.leads.in.time' => array(
            'formAlias' => 'lead_dashboard_leads_in_time_widget'
        ),
        'anonymous.vs.identified.leads' => array(
            'formAlias' => null
        ),
        'map.of.leads' => array(
            'formAlias' => null
        )
    );

    /**
     * Set a widget detail when needed 
     *
     * @param WidgetDetailEvent $event
     *
     * @return void
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event)
    {
        if ($event->getType() == 'created.leads.in.time') {
            $model = $this->factory->getModel('lead');
            $widget = $event->getWidget();
            $params = $widget->getParams();

            // Make sure the params exist
            if (empty($params['amount']) || empty($params['timeUnit'])) {
                $event->setErrorMessage('mautic.core.configuration.value.not.set');
            } else {
                $data = array(
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 70,
                    'chartData'   => $model->getLeadsLineChartData($params['amount'], $params['timeUnit'])
                );

                $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
                $event->setTemplateData($data);
            }
            
            $event->stopPropagation();
        }

        if ($event->getType() == 'anonymous.vs.identified.leads') {
            $model = $this->factory->getModel('lead');
            $widget = $event->getWidget();
            $params = $widget->getParams();

            $data = array(
                'chartType'   => 'pie',
                'chartHeight' => $widget->getHeight() - 70,
                'chartData'   => $model->getAnonymousVsIdentifiedPieChartData()
            );

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->setTemplateData($data);
            
            $event->stopPropagation();
        }

        if ($event->getType() == 'map.of.leads') {
            $model = $this->factory->getModel('lead');
            $widget = $event->getWidget();
            $params = $widget->getParams();

            $data = array(
                'height' => $widget->getHeight() - 70,
                'data'   => $model->getLeadMapData()
            );

            $event->setTemplate('MauticCoreBundle:Helper:map.html.php');
            $event->setTemplateData($data);
            
            $event->stopPropagation();
        }
    }
}
