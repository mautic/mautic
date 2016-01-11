<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\AssetBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'asset';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'asset.downloads.in.time' => array(
            'formAlias' => 'asset_dashboard_downloads_in_time_widget'
        ),
        'unique.vs.repetitive.downloads' => array(
            'formAlias' => null
        ),
        'popular.assets' => array(
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
        if ($event->getType() == 'asset.downloads.in.time') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            // Make sure the params exist
            if (empty($params['amount']) || empty($params['timeUnit'])) {
                $event->setErrorMessage('mautic.core.configuration.value.not.set');
            } else {
                if (!$event->isCached()) {
                    $model = $this->factory->getModel('asset');
                    $event->setTemplateData(array(
                        'chartType'   => 'line',
                        'chartHeight' => $widget->getHeight() - 80,
                        'chartData'   => $model->getDownloadsLineChartData($params['amount'], $params['timeUnit'])
                    ));
                }    
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'unique.vs.repetitive.downloads') {
            if (!$event->isCached()) {
                $model = $this->factory->getModel('asset');
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $model->getUniqueVsRepetitivePieChartData()
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'popular.assets') {
            if (!$event->isCached()) {
                $repo   = $this->factory->getModel('asset')->getRepository();

                // Count the pages limit from the widget height
                $limit  = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                $assets = $repo->getPopularAssets($limit);
                $items  = array();

                // Build table rows with links
                if ($assets) {
                    foreach ($assets as &$asset) {
                        $assetUrl = $this->factory->getRouter()->generate('mautic_asset_action', array('objectAction' => 'view', 'objectId' => $asset['id']));
                        $row = array(
                            $assetUrl => $asset['title'],
                            $asset['downloadCount']
                        );
                        $items[] = $row;
                    }
                }
                
                $event->setTemplateData(array(
                    'headItems'   => array(
                        'mautic.dashboard.label.title',
                        'mautic.dashboard.label.downloads'
                    ),
                    'bodyItems'   => $items
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }
    }
}
