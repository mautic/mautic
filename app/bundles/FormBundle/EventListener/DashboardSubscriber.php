<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\FormBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\FormBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'form';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'submissions.in.time' => array(),
        'top.submission.referrers' => array(),
        'top.submitters' => array(),
        'created.forms' => array()
    );
    
    /**
     * Define permissions to see those widgets
     *
     * @var array
     */
    protected $permissions = array(
        'form:forms:viewown',
        'form:forms:viewother'
    );

    /**
     * @var SubmissionModel
     */
    protected $formSubmissionModel;

    /**
     * @var FormModel
     */
    protected $formModel;

    /**
     * DashboardSubscriber constructor.
     *
     * @param SubmissionModel $formSubmissionModel
     * @param FormModel       $formModel
     */
    public function __construct(SubmissionModel $formSubmissionModel, FormModel $formModel)
    {
        $this->formModel = $formModel;
        $this->formSubmissionModel = $formSubmissionModel;
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
        $canViewOthers = $event->hasPermission('form:forms:viewother');

        if ($event->getType() == 'submissions.in.time') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $event->setTemplateData(array(
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $this->formSubmissionModel->getSubmissionsLineChartData(
                        $params['timeUnit'],
                        $params['dateFrom'],
                        $params['dateTo'],
                        $params['dateFormat'],
                        $canViewOthers
                    )
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'top.submission.referrers') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the pages limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $referrers = $this->formSubmissionModel->getTopSubmissionReferrers($limit, $params['dateFrom'], $params['dateTo'], $canViewOthers);
                $items = array();

                // Build table rows with links
                if ($referrers) {
                    foreach ($referrers as &$referrer) {
                        $row = array(
                            array(
                                'value' => $referrer['referer'],
                                'type' => 'link',
                                'external' => true,
                                'link' => $referrer['referer']
                            ),
                            array(
                                'value' => $referrer['submissions']
                            )
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.form.result.thead.referrer'),
                        $event->getTranslator()->trans('mautic.form.graph.line.submissions')
                    ),
                    'bodyItems'   => $items,
                    'raw'         => $referrers
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'top.submitters') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the pages limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $submitters = $this->formSubmissionModel->getTopSubmitters($limit, $params['dateFrom'], $params['dateTo'], $canViewOthers);
                $items = array();

                // Build table rows with links
                if ($submitters) {
                    foreach ($submitters as &$submitter) {
                        $name = $submitter['lead_id'];
                        $leadUrl = $this->router->generate('mautic_contact_action', array('objectAction' => 'view', 'objectId' => $submitter['lead_id']));
                        if ($submitter['firstname'] || $submitter['lastname']) {
                            $name = trim($submitter['firstname'] . ' ' . $submitter['lastname']);
                        } elseif ($submitter['email']) {
                            $name = $submitter['email'];
                        }

                        $row = array(
                            array(
                                'value' => $name,
                                'type' => 'link',
                                'link' => $leadUrl
                            ),
                            array(
                                'value' => $submitter['submissions']
                            )
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.form.lead'),
                        $event->getTranslator()->trans('mautic.form.graph.line.submissions')
                    ),
                    'bodyItems'   => $items,
                    'raw'         => $submitters
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'created.forms') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the forms limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $forms = $this->formModel->getFormList($limit, $params['dateFrom'], $params['dateTo'], array(), array('canViewOthers' => true));
                $items = array();

                // Build table rows with links
                if ($forms) {
                    foreach ($forms as &$form) {
                        $formUrl = $this->router->generate('mautic_form_action', array('objectAction' => 'view', 'objectId' => $form['id']));
                        $row = array(
                            array(
                                'value' => $form['name'],
                                'type' => 'link',
                                'link' => $formUrl
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
                    'raw'         => $forms
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }
    }
}
