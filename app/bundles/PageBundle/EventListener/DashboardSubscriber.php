<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\PageBundle\Model\PageModel;

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
    protected $bundle = 'page';

    /**
     * Define the widget(s).
     *
     * @var string
     */
    protected $types = [
        'page.hits.in.time' => [
            'formAlias' => 'page_dashboard_hits_in_time_widget',
        ],
        'unique.vs.returning.leads' => [],
        'dwell.times'               => [],
        'popular.pages'             => [],
        'created.pages'             => [],
        'device.granularity'        => [],
    ];

    /**
     * Define permissions to see those widgets.
     *
     * @var array
     */
    protected $permissions = [
        'page:pages:viewown',
        'page:pages:viewother',
    ];

    /**
     * @var PageModel
     */
    protected $pageModel;

    /**
     * DashboardSubscriber constructor.
     *
     * @param PageModel $pageModel
     */
    public function __construct(PageModel $pageModel)
    {
        $this->pageModel = $pageModel;
    }

    /**
     * Set a widget detail when needed.
     *
     * @param WidgetDetailEvent $event
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event)
    {
        $this->checkPermissions($event);
        $canViewOthers = $event->hasPermission('page:pages:viewother');

        if ($event->getType() == 'page.hits.in.time') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (isset($params['flag'])) {
                $params['filter']['flag'] = $params['flag'];
            }

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->pageModel->getHitsLineChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $params['filter'],
                        $canViewOthers
                    ),
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'unique.vs.returning.leads') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $event->setTemplateData([
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $this->pageModel->getNewVsReturningPieChartData($params['dateFrom'], $params['dateTo'], [], $canViewOthers),
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'dwell.times') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $event->setTemplateData([
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $this->pageModel->getDwellTimesPieChartData($params['dateFrom'], $params['dateTo'], [], $canViewOthers),
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'popular.pages') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the pages limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $pages = $this->pageModel->getPopularPages($limit, $params['dateFrom'], $params['dateTo'], [], $canViewOthers);
                $items = [];

                // Build table rows with links
                if ($pages) {
                    foreach ($pages as &$page) {
                        $pageUrl = $this->router->generate('mautic_page_action', ['objectAction' => 'view', 'objectId' => $page['id']]);
                        $row     = [
                            [
                                'value' => $page['title'],
                                'type'  => 'link',
                                'link'  => $pageUrl,
                            ],
                            [
                                'value' => $page['hits'],
                            ],
                        ];
                        $items[] = $row;
                    }
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                        'mautic.dashboard.label.hits',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $pages,
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'created.pages') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the pages limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $pages = $this->pageModel->getPageList($limit, $params['dateFrom'], $params['dateTo'], [], $canViewOthers);
                $items = [];

                // Build table rows with links
                if ($pages) {
                    foreach ($pages as &$page) {
                        $pageUrl = $this->router->generate('mautic_page_action', ['objectAction' => 'view', 'objectId' => $page['id']]);
                        $row     = [
                            [
                                'value' => $page['name'],
                                'type'  => 'link',
                                'link'  => $pageUrl,
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
                    'raw'       => $pages,
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'device.granularity') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'pie',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->pageModel->getDeviceGranularityData(
                        $params['dateFrom'],
                        $params['dateTo'],
                        [],
                        $canViewOthers
                    ),
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }
    }
}
