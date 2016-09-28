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
use Mautic\PageBundle\Model\PageModel;

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
            'formAlias' => 'page_dashboard_hits_in_time_widget'
        ),
        'unique.vs.returning.leads' => array(),
        'dwell.times' => array(),
        'popular.pages' => array(),
        'created.pages' => array(),
        'device.granularity' => array()
    );

    /**
     * Define permissions to see those widgets
     *
     * @var array
     */
    protected $permissions = array(
        'page:pages:viewown',
        'page:pages:viewother'
    );

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
     * Set a widget detail when needed 
     *
     * @param WidgetDetailEvent $event
     *
     * @return void
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
                $event->setTemplateData(array(
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->pageModel->getHitsLineChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $params['filter'],
                        $canViewOthers
                    )
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'unique.vs.returning.leads') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $this->pageModel->getNewVsReturningPieChartData($params['dateFrom'], $params['dateTo'], array(), $canViewOthers)
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'dwell.times') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $this->pageModel->getDwellTimesPieChartData($params['dateFrom'], $params['dateTo'], array(), $canViewOthers)
                ));
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

                $pages = $this->pageModel->getPopularPages($limit, $params['dateFrom'], $params['dateTo'], array(), $canViewOthers);
                $items = array();

                // Build table rows with links
                if ($pages) {
                    foreach ($pages as &$page) {
                        $pageUrl = $this->router->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $page['id']));
                        $row = array(
                            array(
                                'value' => $page['title'],
                                'type' => 'link',
                                'link' => $pageUrl
                            ),
                            array(
                                'value' => $page['hits']
                            )
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.dashboard.label.title'),
                        $event->getTranslator()->trans('mautic.dashboard.label.hits')
                    ),
                    'bodyItems'   => $items,
                    'raw'         => $pages
                ));
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

                $pages = $this->pageModel->getPageList($limit, $params['dateFrom'], $params['dateTo'], array(), $canViewOthers);
                $items = array();

                // Build table rows with links
                if ($pages) {
                    foreach ($pages as &$page) {
                        $pageUrl = $this->router->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $page['id']));
                        $row = array(
                            array(
                                'value' => $page['name'],
                                'type' => 'link',
                                'link' => $pageUrl
                            )
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.dashboard.label.title')
                    ),
                    'bodyItems'   => $items,
                    'raw'         => $pages
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'device.granularity') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->pageModel->getDeviceGranularityData(
                        $params['dateFrom'],
                        $params['dateTo'],
                        array(),
                        $canViewOthers
                    )
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }
    }
}
