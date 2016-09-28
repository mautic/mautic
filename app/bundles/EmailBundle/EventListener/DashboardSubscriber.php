<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\EmailBundle\Model\EmailModel;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'email';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'emails.in.time' => array(
            'formAlias' => 'email_dashboard_emails_in_time_widget'
        ),
        'ignored.vs.read.emails' => array(),
        'upcoming.emails' => array(),
        'most.sent.emails' => array(),
        'most.read.emails' => array(),
        'created.emails' => array(),
        'device.granularity.email' => array()
    );

    /**
     * Define permissions to see those widgets
     *
     * @var array
     */
    protected $permissions = array(
        'email:emails:viewown',
        'email:emails:viewother'
    );

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
     * Set a widget detail when needed 
     *
     * @param WidgetDetailEvent $event
     *
     * @return void
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

            if (!$event->isCached()) {
                $event->setTemplateData(array(
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getEmailsLineChartData(
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

        if ($event->getType() == 'ignored.vs.read.emails') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getIgnoredVsReadPieChartData($params['dateFrom'], $params['dateTo'], array(), $canViewOthers)
                ));
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
            $event->setTemplateData(array('upcomingEmails' => $upcomingEmails));
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
                    array(),
                    array('groupBy' => 'sends', 'canViewOthers' => $canViewOthers)
                );
                $items = array();

                // Build table rows with links
                if ($emails) {
                    foreach ($emails as &$email) {
                        $emailUrl = $this->router->generate('mautic_email_action', array('objectAction' => 'view', 'objectId' => $email['id']));
                        $row = array(
                            array(
                                'value' => $email['name'],
                                'type' => 'link',
                                'link' => $emailUrl
                            ),
                            array(
                                'value' => $email['count']
                            )
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.dashboard.label.title'),
                        $event->getTranslator()->trans('mautic.email.label.sends')
                    ),
                    'bodyItems'   => $items,
                    'raw'         => $emails
                ));
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
                    array(),
                    array('groupBy' => 'reads', 'canViewOthers' => $canViewOthers)
                );
                $items = array();

                // Build table rows with links
                if ($emails) {
                    foreach ($emails as &$email) {
                        $emailUrl = $this->router->generate('mautic_email_action', array('objectAction' => 'view', 'objectId' => $email['id']));
                        $row = array(
                            array(
                                'value' => $email['name'],
                                'type' => 'link',
                                'link' => $emailUrl
                            ),
                            array(
                                'value' => $email['count']
                            )
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.dashboard.label.title'),
                        $event->getTranslator()->trans('mautic.email.label.reads')
                    ),
                    'bodyItems'   => $items,
                    'raw'         => $emails
                ));
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
                    array(),
                    array('groupBy' => 'creations', 'canViewOthers' => $canViewOthers)
                );
                $items = array();

                // Build table rows with links
                if ($emails) {
                    foreach ($emails as &$email) {
                        $emailUrl = $this->router->generate(
                            'mautic_email_action', 
                            array(
                                'objectAction' => 'view',
                                'objectId' => $email['id']
                            )
                        );
                        $row = array(
                            array(
                                'value' => $email['name'],
                                'type' => 'link',
                                'link' => $emailUrl
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
                    'raw'         => $emails
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }
        if ($event->getType() == 'device.granularity.email') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {

                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->emailModel->getDeviceGranularityPieChartData(
                        $params['dateFrom'],
                        $params['dateTo'],
                        $canViewOthers
                    )
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }
    }
}
