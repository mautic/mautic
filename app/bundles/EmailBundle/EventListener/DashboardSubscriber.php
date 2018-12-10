<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\EmailBundle\Model\EmailModel;

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
    protected $bundle = 'email';

    /**
     * Define the widget(s).
     *
     * @var string
     */
    protected $types = [
        'emails.in.time' => [
            'formAlias' => 'email_dashboard_emails_in_time_widget',
        ],
        'sent.email.to.contacts' => [
            'formAlias' => 'email_dashboard_sent_email_to_contacts_widget',
        ],
        'most.hit.email.redirects' => [
            'formAlias' => 'email_dashboard_most_hit_email_redirects_widget',
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

    /**
     * @var EmailModel
     */
    protected $emailModel;

    /**
     * DashboardSubscriber constructor.
     *
     * @param EmailModel $emailModel
     */
    public function __construct(EmailModel $emailModel)
    {
        $this->emailModel = $emailModel;
    }

    /**
     * Set a widget detail when needed.
     *
     * @param WidgetDetailEvent $event
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event)
    {
        $this->checkPermissions($event);
        $canViewOthers = $event->hasPermission('email:emails:viewother');

        if ($event->getType() == 'emails.in.time') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (isset($params['flag'])) {
                $params['filter']['flag'] = $params['flag'];
            }
            if (isset($params['dataset'])) {
                $params['filter']['dataset'] = $params['dataset'];
            }
            if (isset($params['companyId'])) {
                $params['filter']['companyId'] = $params['companyId'];
            }
            if (isset($params['campaignId'])) {
                $params['filter']['campaignId'] = $params['campaignId'];
            }
            if (isset($params['segmentId'])) {
                $params['filter']['segmentId'] = $params['segmentId'];
            }

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getEmailsLineChartData(
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

        if ($event->getType() == 'sent.email.to.contacts') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                if (empty($params['limit'])) {
                    // Count the emails limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }
                $companyId = null;
                if (isset($params['filter']['companyId'])) {
                    $companyId = $params['filter']['companyId'];
                }
                $campaignId = null;
                if (isset($params['filter']['campaignId'])) {
                    $campaignId = $params['filter']['campaignId'];
                }
                $segmentId = null;
                if (isset($params['filter']['segmentId'])) {
                    $segmentId = $params['filter']['segmentId'];
                }

                $headItems = [
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
                            $limit,
                            $params['dateFrom'],
                            $params['dateTo'],
                            ['groupBy' => 'sends', 'canViewOthers' => $canViewOthers],
                            $companyId,
                            $campaignId,
                            $segmentId
                        ),
                    ]
                );
            }

            $event->setTemplate('MauticEmailBundle:SubscribedEvents:Dashboard/Sent.email.to.contacts.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'most.hit.email.redirects') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                if (empty($params['limit'])) {
                    // Count the emails limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }
                $companyId = null;
                if (isset($params['filter']['companyId'])) {
                    $companyId = $params['filter']['companyId'];
                }
                $campaignId = null;
                if (isset($params['filter']['campaignId'])) {
                    $campaignId = $params['filter']['campaignId'];
                }
                $segmentId = null;
                if (isset($params['filter']['segmentId'])) {
                    $segmentId = $params['filter']['segmentId'];
                }
                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.url',
                        'mautic.dashboard.label.unique.hit.count',
                        'mautic.dashboard.label.total.hit.count',
                        'mautic.dashboard.label.email.id',
                        'mautic.dashboard.label.email.name',
                    ],
                    'bodyItems' => $this->emailModel->getMostHitEmailRedirects(
                        $limit,
                        $params['dateFrom'],
                        $params['dateTo'],
                        ['groupBy' => 'sends', 'canViewOthers' => $canViewOthers],
                        $companyId,
                        $campaignId,
                        $segmentId
                    ),
                ]);
            }

            $event->setTemplate('MauticEmailBundle:SubscribedEvents:Dashboard/Most.hit.email.redirects.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'ignored.vs.read.emails') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'pie',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getIgnoredVsReadPieChartData($params['dateFrom'], $params['dateTo'], [], $canViewOthers),
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'upcoming.emails') {
            $widget = $event->getWidget();
            $params = $widget->getParams();
            $height = $widget->getHeight();
            $limit  = round(($height - 80) / 60);

            $upcomingEmails = $this->emailModel->getUpcomingEmails($limit, $canViewOthers);

            $event->setTemplate('MauticDashboardBundle:Dashboard:upcomingemails.html.php');
            $event->setTemplateData(['upcomingEmails' => $upcomingEmails]);
            $event->stopPropagation();
        }

        if ($event->getType() == 'most.sent.emails') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the emails limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $emails = $this->emailModel->getEmailStatList(
                    $limit,
                    $params['dateFrom'],
                    $params['dateTo'],
                    [],
                    ['groupBy' => 'sends', 'canViewOthers' => $canViewOthers]
                );
                $items = [];

                // Build table rows with links
                if ($emails) {
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

            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'most.read.emails') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the emails limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $emails = $this->emailModel->getEmailStatList(
                    $limit,
                    $params['dateFrom'],
                    $params['dateTo'],
                    [],
                    ['groupBy' => 'reads', 'canViewOthers' => $canViewOthers]
                );
                $items = [];

                // Build table rows with links
                if ($emails) {
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

            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'created.emails') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the emails limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $emails = $this->emailModel->getEmailList(
                    $limit,
                    $params['dateFrom'],
                    $params['dateTo'],
                    [],
                    ['groupBy' => 'creations', 'canViewOthers' => $canViewOthers]
                );
                $items = [];

                // Build table rows with links
                if ($emails) {
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
                }

                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                    ],
                    'bodyItems' => $items,
                    'raw'       => $emails,
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }
        if ($event->getType() == 'device.granularity.email') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData([
                    'chartType'   => 'pie',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getDeviceGranularityPieChartData(
                        $params['dateFrom'],
                        $params['dateTo'],
                        $canViewOthers
                    ),
                ]);
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }
    }
}
