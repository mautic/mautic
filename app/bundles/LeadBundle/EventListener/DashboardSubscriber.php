<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\LeadBundle\Form\Type\DashboardLeadsInTimeWidgetType;
use Mautic\LeadBundle\Form\Type\DashboardLeadsLifetimeWidgetType;
use Mautic\LeadBundle\Form\Type\DashboardSegmentsBuildTime;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s).
     *
     * @var string
     */
    protected $bundle = 'lead';

    /**
     * Define the widget(s).
     *
     * @var string
     */
    protected $types = [
        'created.leads.in.time' => [
            'formAlias' => DashboardLeadsInTimeWidgetType::class,
        ],
        'anonymous.vs.identified.leads' => [],
        'lead.lifetime'                 => [
            'formAlias' => DashboardLeadsLifetimeWidgetType::class,
        ],
        'map.of.leads'            => [],
        'top.lists'               => [],
        'segments.build.time'     => [
            'formAlias' => DashboardSegmentsBuildTime::class,
        ],
        'top.creators'  => [],
        'top.owners'    => [],
        'created.leads' => [],
    ];

    /**
     * Define permissions to see those widgets.
     *
     * @var array
     */
    protected $permissions = [
        'lead:leads:viewown',
        'lead:leads:viewother',
    ];

    public function __construct(
        protected LeadModel $leadModel,
        protected ListModel $leadListModel,
        protected RouterInterface $router,
        protected TranslatorInterface $translator,
        protected DateHelper $dateHelper
    ) {
    }

    /**
     * Set a widget detail when needed.
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event): void
    {
        $this->checkPermissions($event);
        $canViewOthers = $event->hasPermission('lead:leads:viewother');

        if ('created.leads.in.time' == $event->getType()) {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (isset($params['flag'])) {
                $params['filter']['flag'] = $params['flag'];
            }

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->leadModel->getLeadsLineChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $params['filter'],
                        $canViewOthers
                    ),
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/chart.html.twig');
            $event->stopPropagation();

            return;
        }

        if ('anonymous.vs.identified.leads' == $event->getType()) {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $event->setTemplateData([
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $this->leadModel->getAnonymousVsIdentifiedPieChartData($params['dateFrom'], $params['dateTo'], $canViewOthers),
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/chart.html.twig');
            $event->stopPropagation();

            return;
        }

        if ('map.of.leads' == $event->getType()) {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $event->setTemplateData([
                    'height' => $event->getWidget()->getHeight() - 80,
                    'data'   => $this->leadModel->getLeadMapData($params['dateFrom'], $params['dateTo'], [], $canViewOthers),
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/map.html.twig');
            $event->stopPropagation();

            return;
        }

        if ('top.lists' == $event->getType()) {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the list limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $lists = $this->leadListModel->getTopLists($limit, $params['dateFrom'], $params['dateTo'], $canViewOthers);
                $items = [];

                // Build table rows with links
                foreach ($lists as &$list) {
                    $listUrl    = $this->router->generate('mautic_segment_action', ['objectAction' => 'edit', 'objectId' => $list['id']]);
                    $contactUrl = $this->router->generate('mautic_contact_index', ['search' => 'segment:'.$list['alias']]);
                    $row        = [
                        [
                            'value' => $list['name'],
                            'type'  => 'link',
                            'link'  => $listUrl,
                        ],
                        [
                            'value' => $list['leads'],
                            'type'  => 'link',
                            'link'  => $contactUrl,
                        ],
                    ];
                    $items[] = $row;
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                        'mautic.lead.leads',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $lists,
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/table.html.twig');
            $event->stopPropagation();

            return;
        }

        if ('lead.lifetime' == $event->getType()) {
            $params = $event->getWidget()->getParams();

            if (empty($params['limit'])) {
                // Count the list limit from the widget height
                $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
            } else {
                $limit = $params['limit'];
            }

            $maxSegmentsToshow        = 4;
            $params['filter']['flag'] = [];

            if (isset($params['flag'])) {
                $params['filter']['flag'] = $params['flag'];
                $maxSegmentsToshow        = count($params['filter']['flag']);
            }

            $lists = $this->leadListModel->getLifeCycleSegments($maxSegmentsToshow, $params['dateFrom'], $params['dateTo'], $canViewOthers, $params['filter']['flag']);
            $items = [];

            if (empty($lists)) {
                $lists[] = [
                    'leads' => 0,
                    'id'    => 0,
                    'name'  => $event->getTranslator()->trans('mautic.lead.all.leads'),
                    'alias' => '',
                ];
            }

            // Build table rows with links
            if ($lists) {
                $stages            = [];
                $deviceGranularity = [];

                foreach ($lists as &$list) {
                    if ('' != $list['alias']) {
                        $listUrl = $this->router->generate('mautic_contact_index', ['search' => 'segment:'.$list['alias']]);
                    } else {
                        $listUrl = $this->router->generate('mautic_contact_index', []);
                    }
                    if ($list['id']) {
                        $params['filter']['leadlist_id'] = [
                            'value'            => $list['id'],
                            'list_column_name' => 't.id',
                        ];
                    } else {
                        unset($params['filter']['leadlist_id']);
                    }

                    $column = $this->leadListModel->getLifeCycleSegmentChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $params['filter'],
                        $canViewOthers,
                        $list['name']
                    );
                    $items['columnName'][] = $list['name'];
                    $items['value'][]      = $list['leads'];
                    $items['link'][]       = $listUrl;
                    $items['chartItems'][] = $column;

                    $stages[] = $this->leadListModel->getStagesBarChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $params['filter'],
                        $canViewOthers
                    );

                    $deviceGranularity[] = $this->leadListModel->getDeviceGranularityData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $params['filter'],
                        $canViewOthers
                    );
                }
                $width = 100 / count($lists);

                $event->setTemplateData([
                    'columnName'  => $items['columnName'],
                    'value'       => $items['value'],
                    'width'       => $width,
                    'link'        => $items['link'],
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 180,
                    'chartItems'  => $items['chartItems'],
                    'stages'      => $stages,
                    'devices'     => $deviceGranularity,
                ]);
                $event->setTemplate('@MauticCore/Helper/lifecycle.html.twig');
                $event->stopPropagation();
            }

            return;
        }

        if ('top.owners' == $event->getType()) {
            if (!$canViewOthers) {
                $event->setErrorMessage($this->translator->trans('mautic.dashboard.missing.permission', ['%section%' => $this->bundle]));
                $event->stopPropagation();

                return;
            }

            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the list limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $owners = $this->leadModel->getTopOwners($limit, $params['dateFrom'], $params['dateTo']);
                $items  = [];

                // Build table rows with links
                foreach ($owners as &$owner) {
                    $ownerUrl = $this->router->generate('mautic_user_action', ['objectAction' => 'edit', 'objectId' => $owner['owner_id']]);
                    $row      = [
                        [
                            'value' => $owner['first_name'].' '.$owner['last_name'],
                            'type'  => 'link',
                            'link'  => $ownerUrl,
                        ],
                        [
                            'value' => $owner['leads'],
                        ],
                    ];
                    $items[] = $row;
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.user.account.permissions.editname',
                        'mautic.lead.leads',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $owners,
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/table.html.twig');
            $event->stopPropagation();

            return;
        }

        if ('top.creators' == $event->getType()) {
            if (!$canViewOthers) {
                $event->setErrorMessage($this->translator->trans('mautic.dashboard.missing.permission', ['%section%' => $this->bundle]));
                $event->stopPropagation();

                return;
            }

            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the list limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $creators = $this->leadModel->getTopCreators($limit, $params['dateFrom'], $params['dateTo']);
                $items    = [];

                // Build table rows with links
                foreach ($creators as &$creator) {
                    $creatorUrl = $this->router->generate('mautic_user_action', ['objectAction' => 'edit', 'objectId' => $creator['created_by']]);
                    $row        = [
                        [
                            'value' => $creator['created_by_user'],
                            'type'  => 'link',
                            'link'  => $creatorUrl,
                        ],
                        [
                            'value' => $creator['leads'],
                        ],
                    ];
                    $items[] = $row;
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.user.account.permissions.editname',
                        'mautic.lead.leads',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $creators,
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/table.html.twig');
            $event->stopPropagation();

            return;
        }

        if ('created.leads' == $event->getType()) {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the leads limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $leads = $this->leadModel->getLeadList($limit, $params['dateFrom'], $params['dateTo'], $canViewOthers, []);
                $items = [];

                if (empty($leads)) {
                    $leads[] = [
                        'name' => $this->translator->trans('mautic.report.report.noresults'),
                    ];
                }

                // Build table rows with links
                foreach ($leads as &$lead) {
                    $leadUrl = isset($lead['id']) ? $this->router->generate('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $lead['id']]) : '';
                    $type    = isset($lead['id']) ? 'link' : 'text';
                    $row     = [
                        [
                            'value' => $lead['name'],
                            'type'  => $type,
                            'link'  => $leadUrl,
                        ],
                    ];
                    $items[] = $row;
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $leads,
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/table.html.twig');
            $event->stopPropagation();

            return;
        }

        if ('segments.build.time' == $event->getType()) {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the list limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $segments = $this->leadListModel->getSegmentsBuildTime($limit, $params['order'] ?? 'desc', $params['segments'] ?? [], $canViewOthers);
                $items    = [];

                // Build table rows with links
                foreach ($segments as $segment) {
                    $listUrl    = $this->router->generate('mautic_segment_action', ['objectAction' => 'view', 'objectId' => $segment->getId()]);
                    $buildTime  = explode(':', gmdate('H:i:s', (int) $segment->getLastBuiltTime()));
                    $timeString = $this->dateHelper->formatRange(
                        new \DateInterval("PT{$buildTime[0]}H{$buildTime[1]}M{$buildTime[2]}S")
                    );

                    $row        = [
                        [
                            'value' => $segment->getName(),
                            'type'  => 'link',
                            'link'  => $listUrl,
                        ],
                        [
                            'value' => $segment->getCreatedByUser(),
                        ],
                        [
                            'value' => $timeString,
                        ],
                    ];
                    $items[] = $row;
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                        'mautic.core.createdby',
                        'mautic.lead.list.last_built_time',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $segments,
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/table.html.twig');
            $event->stopPropagation();

            return;
        }
    }
}
