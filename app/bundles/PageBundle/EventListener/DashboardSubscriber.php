<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PageBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\PageBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'page';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'page.hits.in.time' => array(
            'formAlias' => 'lead_dashboard_leads_in_time_widget'
        ),
        'unique.vs.returning.leads' => array(
            'formAlias' => null
        ),
        'dwell.times' => array(
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
        if ($event->getType() == 'page.hits.in.time') {
            $model = $this->factory->getModel('page');
            $widget = $event->getWidget();
            $params = $widget->getParams();

            // Make sure the params exist
            if (empty($params['amount']) || empty($params['timeUnit'])) {
                $event->setErrorMessage('mautic.core.configuration.value.not.set');
            } else {
                $data = array(
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 70,
                    'chartData'   => $model->getHitsBarChartData($params['amount'], $params['timeUnit'])
                );

                $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
                $event->setTemplateData($data);
            }
            
            $event->stopPropagation();
        }

        if ($event->getType() == 'unique.vs.returning.leads') {
            $model = $this->factory->getModel('page');
            $widget = $event->getWidget();
            $params = $widget->getParams();

            $data = array(
                'chartType'   => 'pie',
                'chartHeight' => $widget->getHeight() - 70,
                'chartData'   => $model->getNewVsReturningPieChartData()
            );

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->setTemplateData($data);
            
            $event->stopPropagation();
        }

        if ($event->getType() == 'dwell.times') {
            $model = $this->factory->getModel('page');
            $widget = $event->getWidget();
            $params = $widget->getParams();

            $data = array(
                'chartType'   => 'pie',
                'chartHeight' => $widget->getHeight() - 70,
                'chartData'   => $model->getDwellTimesPieChartData()
            );

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->setTemplateData($data);
            
            $event->stopPropagation();
        }
    }
}
