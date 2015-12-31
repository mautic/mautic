<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'email';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'emails.in.time' => array(
            'formAlias' => 'email_dashboard_emails_in_time_widget'
        ),
        'ignored.vs.read.emails' => array()
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
        if ($event->getType() == 'emails.in.time') {
            $model = $this->factory->getModel('email');
            $widget = $event->getWidget();
            $params = $widget->getParams();

            // Make sure the params exist
            if (empty($params['amount']) || empty($params['timeUnit'])) {
                $event->setErrorMessage('mautic.core.configuration.value.not.set');
            } else {
                $data = array(
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 70,
                    'chartData'   => $model->getEmailsLineChartData($params['amount'], $params['timeUnit'])
                );

                $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
                $event->setTemplateData($data);
            }
            
            $event->stopPropagation();
        }

        if ($event->getType() == 'ignored.vs.read.emails') {
            $model = $this->factory->getModel('email');
            $widget = $event->getWidget();
            $params = $widget->getParams();

            $data = array(
                'chartType'   => 'pie',
                'chartHeight' => $widget->getHeight() - 70,
                'chartData'   => $model->getIgnoredVsReadPieChartData()
            );

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->setTemplateData($data);
            
            $event->stopPropagation();
        }
    }
}
