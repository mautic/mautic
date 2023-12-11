<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\EmailBundle\Form\Type\DashboardEmailsInTimeWidgetType;
use Mautic\EmailBundle\Form\Type\DashboardMostHitEmailRedirectsWidgetType;
use Mautic\EmailBundle\Form\Type\DashboardSentEmailToContactsWidgetType;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\Routing\RouterInterface;

class DashboardSubscriber extends MainDashboardSubscriber
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
        'emails.in.time' => [
            'formAlias' => DashboardEmailsInTimeWidgetType::class,
        ],
        'sent.email.to.contacts' => [
            'formAlias' => DashboardSentEmailToContactsWidgetType::class,
        ],
        'most.hit.email.redirects' => [
            'formAlias' => DashboardMostHitEmailRedirectsWidgetType::class,
        ],
        'ignored.vs.read.emails'   => [],
        'upcoming.emails'          => [],
        'most.sent.emails'         => [],
        'most.read.emails'         => [],
        'created.emails'           => [],
        'device.granularity.email' => [],
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
        private RouterInterface $router
    ) {
    }

    /**
     * Set a widget detail when needed.
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event): void
    {
        $this->checkPermissions($event);
        $canViewOthers = $event->hasPermission('email:emails:viewother');
        $defaultLimit  = $this->getDefaultLimit($event->getWidget());

        if ('emails.in.time' == $event->getType()) {
            $widget     = $event->getWidget();
            $params     = $widget->getParams();
            $filterKeys = ['flag', 'dataset', 'companyId', 'campaignId', 'segmentId'];

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getEmailsLineChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        ArrayHelper::select($filterKeys, $params),
                        $canViewOthers
                    ),
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/chart.html.twig');
            $event->stopPropagation();
        }

        if ('sent.email.to.contacts' == $event->getType()) {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $headItems  = [
                    'mautic.dashboard.label.contact.id',
                    'mautic.dashboard.label.contact.email.address',
                    'mautic.dashboard.label.contact.open',
                    'mautic.dashboard.label.contact.click',
                    'mautic.dashboard.label.contact.links.clicked',
                    'mautic.dashboard.label.email.id',
                    'mautic.dashboard.label.email.name',
                    'mautic.dashboard.label.segment.id',
                    'mautic.dashboard.label.segment.name',
                    'mautic.dashboard.label.company.id',
                    'mautic.dashboard.label.company.name',
                    'mautic.dashboard.label.campaign.id',
                    'mautic.dashboard.label.campaign.name',
                    'mautic.dashboard.label.date.sent',
                    'mautic.dashboard.label.date.read',
                ];

                $event->setTemplateData(
                    [
                        'headItems' => $headItems,
                        'bodyItems' => $this->emailModel->getSentEmailToContactData(
                            ArrayHelper::getValue('limit', $params, $defaultLimit),
                            $params['dateFrom'],
                            $params['dateTo'],
                            ['groupBy' => 'sends', 'canViewOthers' => $canViewOthers],
                            ArrayHelper::getValue('companyId', $params),
                            ArrayHelper::getValue('campaignId', $params),
                            ArrayHelper::getValue('segmentId', $params)
                        ),
                    ]
                );
            }

            $event->setTemplate('@MauticEmail/SubscribedEvents/Dashboard/Sent.email.to.contacts.html.twig');
            $event->stopPropagation();
        }

        if ('most.hit.email.redirects' == $event->getType()) {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.url',
                        'mautic.dashboard.label.unique.hit.count',
                        'mautic.dashboard.label.total.hit.count',
                        'mautic.dashboard.label.email.id',
                        'mautic.dashboard.label.email.name',
                    ],
                    'bodyItems' => $this->emailModel->getMostHitEmailRedirects(
                        ArrayHelper::getValue('limit', $params, $defaultLimit),
                        $params['dateFrom'],
                        $params['dateTo'],
                        ['groupBy' => 'sends', 'canViewOthers' => $canViewOthers],
                        ArrayHelper::getValue('companyId', $params),
                        ArrayHelper::getValue('campaignId', $params),
                        ArrayHelper::getValue('segmentId', $params)
                    ),
                ]);
            }

            $event->setTemplate('@MauticEmail/SubscribedEvents/Dashboard/Most.hit.email.redirects.html.twig');
            $event->stopPropagation();
        }

        if ('ignored.vs.read.emails' == $event->getType()) {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'pie',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getIgnoredVsReadPieChartData($params['dateFrom'], $params['dateTo'], [], $canViewOthers),
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/chart.html.twig');
            $event->stopPropagation();
        }

        if ('upcoming.emails' == $event->getType()) {
            $widget = $event->getWidget();
            $params = $widget->getParams();
            $height = $widget->getHeight();
            $limit  = round(($height - 80) / 60);

            $upcomingEmails = $this->emailModel->getUpcomingEmails($limit, $canViewOthers);

            $event->setTemplate('@MauticDashboard/Dashboard/upcomingemails.html.twig');
            $event->setTemplateData(['upcomingEmails' => $upcomingEmails]);
            $event->stopPropagation();
        }

        if ('most.sent.emails' == $event->getType()) {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $emails = $this->emailModel->getEmailStatList(
                    ArrayHelper::getValue('limit', $params, $defaultLimit),
                    $params['dateFrom'],
                    $params['dateTo'],
                    [],
                    ['groupBy' => 'sends', 'canViewOthers' => $canViewOthers]
                );
                $items = [];

                // Build table rows with links
                foreach ($emails as &$email) {
                    $emailUrl = $this->router->generate('mautic_email_action', ['objectAction' => 'view', 'objectId' => $email['id']]);
                    $row      = [
                        [
                            'value' => $email['name'],
                            'type'  => 'link',
                            'link'  => $emailUrl,
                        ],
                        [
                            'value' => $email['count'],
                        ],
                    ];
                    $items[] = $row;
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                        'mautic.email.label.sends',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $emails,
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/table.html.twig');
            $event->stopPropagation();
        }

        if ('most.read.emails' == $event->getType()) {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $emails = $this->emailModel->getEmailStatList(
                    ArrayHelper::getValue('limit', $params, $defaultLimit),
                    $params['dateFrom'],
                    $params['dateTo'],
                    [],
                    ['groupBy' => 'reads', 'canViewOthers' => $canViewOthers]
                );
                $items = [];

                // Build table rows with links
                foreach ($emails as &$email) {
                    $emailUrl = $this->router->generate('mautic_email_action', ['objectAction' => 'view', 'objectId' => $email['id']]);
                    $row      = [
                        [
                            'value' => $email['name'],
                            'type'  => 'link',
                            'link'  => $emailUrl,
                        ],
                        [
                            'value' => $email['count'],
                        ],
                    ];
                    $items[] = $row;
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                        'mautic.email.label.reads',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $emails,
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/table.html.twig');
            $event->stopPropagation();
        }

        if ('created.emails' == $event->getType()) {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $emails = $this->emailModel->getEmailList(
                    ArrayHelper::getValue('limit', $params, $defaultLimit),
                    $params['dateFrom'],
                    $params['dateTo'],
                    [],
                    ['groupBy' => 'creations', 'canViewOthers' => $canViewOthers]
                );
                $items = [];

                // Build table rows with links
                foreach ($emails as &$email) {
                    $emailUrl = $this->router->generate(
                        'mautic_email_action',
                        [
                            'objectAction' => 'view',
                            'objectId'     => $email['id'],
                        ]
                    );
                    $row = [
                        [
                            'value' => $email['name'],
                            'type'  => 'link',
                            'link'  => $emailUrl,
                        ],
                    ];
                    $items[] = $row;
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $emails,
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/table.html.twig');
            $event->stopPropagation();
        }
        if ('device.granularity.email' == $event->getType()) {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'pie',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getDeviceGranularityPieChartData(
                        $params['dateFrom'],
                        $params['dateTo']
                    ),
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/chart.html.twig');
            $event->stopPropagation();
        }
    }

    /**
     * Count the row limit from the widget height.
     */
    private function getDefaultLimit(Widget $widget): float
    {
        return round((($widget->getHeight() - 80) / 35) - 1);
    }
}
