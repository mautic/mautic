<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;

/**
 * Class DashboardSubscriber.
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s).
     *
     * @var string
     */
    protected $bundle = 'asset';

    /**
     * Define the widget(s).
     *
     * @var array
     */
    protected $types = [
        'asset.downloads.in.time'        => [],
        'unique.vs.repetitive.downloads' => [],
        'popular.assets'                 => [],
        'created.assets'                 => [],
    ];

    /**
     * Define permissions to see those widgets.
     *
     * @var array
     */
    protected $permissions = [
        'asset:assets:viewown',
        'asset:assets:viewother',
    ];

    /**
     * @var AssetModel
     */
    protected $assetModel;

    /**
     * DashboardSubscriber constructor.
     *
     * @param AssetModel $assetModel
     */
    public function __construct(AssetModel $assetModel)
    {
        $this->assetModel = $assetModel;
    }

    /**
     * Set a widget detail when needed.
     *
     * @param WidgetDetailEvent $event
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event)
    {
        $this->checkPermissions($event);
        $canViewOthers = $event->hasPermission('asset:assets:viewother');

        if ($event->getType() == 'asset.downloads.in.time') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->assetModel->getDownloadsLineChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $canViewOthers
                    ),
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'unique.vs.repetitive.downloads') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $model  = $this->factory->getModel('asset');
                $event->setTemplateData([
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $this->assetModel->getUniqueVsRepetitivePieChartData($params['dateFrom'], $params['dateTo'], $canViewOthers),
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'popular.assets') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the pages limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $assets = $this->assetModel->getPopularAssets($limit, $params['dateFrom'], $params['dateTo'], $canViewOthers);
                $items  = [];

                // Build table rows with links
                if ($assets) {
                    foreach ($assets as &$asset) {
                        $assetUrl = $this->router->generate('mautic_asset_action', ['objectAction' => 'view', 'objectId' => $asset['id']]);
                        $row      = [
                            [
                                'value' => $asset['title'],
                                'type'  => 'link',
                                'link'  => $assetUrl,
                            ],
                            [
                                'value' => $asset['download_count'],
                            ],
                        ];
                        $items[] = $row;
                    }
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                        'mautic.dashboard.label.downloads',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $assets,
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'created.assets') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the assets limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $assets = $this->assetModel->getAssetList($limit, $params['dateFrom'], $params['dateTo'], [], ['canViewOthers' => $canViewOthers]);
                $items  = [];

                // Build table rows with links
                if ($assets) {
                    foreach ($assets as &$asset) {
                        $assetUrl = $this->router->generate('mautic_asset_action', ['objectAction' => 'view', 'objectId' => $asset['id']]);
                        $row      = [
                            [
                                'value' => $asset['name'],
                                'type'  => 'link',
                                'link'  => $assetUrl,
                            ],
                        ];
                        $items[] = $row;
                    }
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $assets,
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }
    }
}
