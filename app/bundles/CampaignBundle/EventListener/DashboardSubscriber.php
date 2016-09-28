<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\CampaignBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'campaign';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'events.in.time' => array(),
        'leads.added.in.time' => array()
    );

    /**
     * Define permissions to see those widgets
     *
     * @var array
     */
    protected $permissions = array(
        'campaign:campaigns:viewown',
        'campaign:campaigns:viewother'
    );

    /**
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * DashboardSubscriber constructor.
     *
     * @param CampaignModel $campaignModel
     * @param EventModel    $campaignEventModel
     */
    public function __construct(CampaignModel $campaignModel, EventModel $campaignEventModel)
    {
        $this->campaignModel      = $campaignModel;
        $this->campaignEventModel = $campaignEventModel;
    }

    /**
     * Set a widget detail when needed 
     *
     * @param WidgetDetailEvent $event
     *
     * @return void
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event)
    {
        $this->checkPermissions($event);
        $canViewOthers = $event->hasPermission('campaign:campaigns:viewother');

        if ($event->getType() == 'events.in.time') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData(array(
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->campaignEventModel->getEventLineChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $canViewOthers
                    )
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'leads.added.in.time') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData(array(
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->campaignModel->getLeadsAddedLineChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $canViewOthers
                    )
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }
    }
}
