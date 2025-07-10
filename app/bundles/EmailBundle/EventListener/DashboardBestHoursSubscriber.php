<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\EmailBundle\Form\Type\DashboardBestHoursWidgetType;
use Mautic\EmailBundle\Model\EmailModel;

class DashboardBestHoursSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s).
     *
     * @var string
     */
    protected $bundle = 'email';

    /**
     * Define the widget(s).
     *
     * @var string
     */
    protected $types = [
        'emails.best.hours' => [
            'formAlias' => DashboardBestHoursWidgetType::class,
        ],
    ];

    /**
     * Define permissions to see those widgets.
     *
     * @var array
     */
    protected $permissions = [
        'email:emails:viewown',
        'email:emails:viewother',
    ];

    public function __construct(
        protected EmailModel $emailModel,
    ) {
    }

    /**
     * Set a widget detail when needed.
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event): void
    {
        $this->checkPermissions($event);
        $canViewOthers = $event->hasPermission('email:emails:viewother');

        if ('emails.best.hours' == $event->getType()) {
            $widget     = $event->getWidget();
            $params     = $widget->getParams();
            $filterKeys = ['companyId', 'campaignId', 'segmentId'];

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'bar',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getBestHours(
                        'date_read',
                        $params['dateFrom'],
                        $params['dateTo'],
                        ArrayHelper::select($filterKeys, $params),
                        $canViewOthers,
                        $params['timeFormat']
                    ),
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/chart.html.twig');
            $event->stopPropagation();
        }
    }
}
