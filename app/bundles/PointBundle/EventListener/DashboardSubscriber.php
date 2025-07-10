<?php

namespace Mautic\PointBundle\EventListener;

use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\PointBundle\Model\PointModel;

class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s).
     *
     * @var string
     */
    protected $bundle = 'point';

    /**
     * Define the widget(s).
     *
     * @var string
     */
    protected $types = [
        'points.in.time' => [],
    ];

    /**
     * Define permissions to see those widgets.
     *
     * @var array
     */
    protected $permissions = [
        'point:points:viewown',
        'point:points:viewother',
    ];

    public function __construct(
        protected PointModel $pointModel,
    ) {
    }

    /**
     * Set a widget detail when needed.
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event): void
    {
        $this->checkPermissions($event);
        $canViewOthers = $event->hasPermission('point:points:viewother');

        if ('points.in.time' == $event->getType()) {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->pointModel->getPointLineChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        [],
                        $canViewOthers
                    ),
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/chart.html.twig');
            $event->stopPropagation();
        }
    }
}
