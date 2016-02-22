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
        'emails.in.time' => array(),
        'ignored.vs.read.emails' => array(),
        'upcoming.emails' => array()
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

            $widget = $event->getWidget();
            $params = $widget->getParams();

            // Make sure the params exist
            if (empty($params['amount']) || empty($params['timeUnit'])) {
                $event->setErrorMessage('mautic.core.configuration.value.not.set');
            } else {
                if (!$event->isCached()) {
                    $model = $this->factory->getModel('email');

                    $event->setTemplateData(array(
                        'chartType'   => 'line',
                        'chartHeight' => $widget->getHeight() - 80,
                        'chartData'   => $model->getEmailsLineChartData($params['amount'], $params['timeUnit'], $params['dateFrom'], $params['dateTo'])
                    ));
                }

                $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            }
            
            $event->stopPropagation();
        }

        if ($event->getType() == 'ignored.vs.read.emails') {
            $model = $this->factory->getModel('email');
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $model->getIgnoredVsReadPieChartData($params['dateFrom'], $params['dateTo'])
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'upcoming.emails') {
            $widget = $event->getWidget();
            $params = $widget->getParams();
            $height = $widget->getHeight();
            $limit  = round(($height - 80) / 60);

            /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository */
            $leadEventLogRepository = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog');
            $upcomingEmails = $leadEventLogRepository->getUpcomingEvents(array('type' => 'email.send', 'scheduled' => 1, 'eventType' => 'action', 'limit' => $limit));

            $leadModel = $this->factory->getModel('lead.lead');

            $event->setTemplate('MauticDashboardBundle:Dashboard:upcomingemails.html.php');
            $event->setTemplateData(array('upcomingEmails' => $upcomingEmails));
            
            $event->stopPropagation();
        }
    }
}
