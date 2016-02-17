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
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!isset($params['dateTo'])) {
                $params['dateTo'] = null;
            }

            // Make sure the params exist
            if (empty($params['amount']) || empty($params['timeUnit'])) {
                $event->setErrorMessage('mautic.core.configuration.value.not.set');
            } else {
                if (!$event->isCached()) {
                    $model = $this->factory->getModel('lead');
                    $event->setTemplateData(array(
                        'chartType'   => 'line',
                        'chartHeight' => $widget->getHeight() - 80,
                        'chartData'   => $model->getLeadsLineChartData($params['amount'], $params['timeUnit'], $params['dateTo'])
                    ));
                }    
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'anonymous.vs.identified.leads') {
            if (!$event->isCached()) {
                $model = $this->factory->getModel('lead');
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $model->getAnonymousVsIdentifiedPieChartData()
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'map.of.leads') {
            if (!$event->isCached()) {
                $model = $this->factory->getModel('lead');
                $event->setTemplateData(array(
                    'height' => $event->getWidget()->getHeight() - 80,
                    'data'   => $model->getLeadMapData()
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:map.html.php');
            $event->stopPropagation();
        }
    }
}
