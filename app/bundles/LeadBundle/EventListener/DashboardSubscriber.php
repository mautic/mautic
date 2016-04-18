<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\LeadBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'lead';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'created.leads.in.time' => array(
            'formAlias' => 'lead_dashboard_leads_in_time_widget'
        ),
        'anonymous.vs.identified.leads' => array(),
        'map.of.leads' => array(),
        'top.lists' => array(),
        'top.creators' => array(),
        'top.owners' => array(),
        'created.leads' => array()
    );

    /**
     * Define permissions to see those widgets
     *
     * @var array
     */
    protected $permissions = array(
        'lead:leads:viewown',
        'lead:leads:viewother'
    );

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
        
        if ($event->getType() == 'created.leads.in.time') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (isset($params['flag'])) {
                $params['filter']['flag'] = $params['flag'];
            }

            if (!$event->isCached()) {
                $model = $this->factory->getModel('lead');
                $event->setTemplateData(array(
                    'chartType'   => 'line',
                    'chartHeight' => $widget->getHeight() - 80,
                    'chartData'   => $model->getLeadsLineChartData(
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
            return;
        }

        if ($event->getType() == 'anonymous.vs.identified.leads') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $model = $this->factory->getModel('lead');
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $model->getAnonymousVsIdentifiedPieChartData($params['dateFrom'], $params['dateTo'], $canViewOthers)
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
            return;
        }

        if ($event->getType() == 'map.of.leads') {
            if (!$event->isCached()) {
                $params = $event->getWidget()->getParams();
                $model = $this->factory->getModel('lead');
                $event->setTemplateData(array(
                    'height' => $event->getWidget()->getHeight() - 80,
                    'data'   => $model->getLeadMapData($params['dateFrom'], $params['dateTo'], $canViewOthers)
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:map.html.php');
            $event->stopPropagation();
            return;
        }

        if ($event->getType() == 'top.lists') {
            if (!$event->isCached()) {
                $model  = $this->factory->getModel('lead.list');
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the list limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $lists = $model->getTopLists($limit, $params['dateFrom'], $params['dateTo'], $canViewOthers);
                $items = array();

                // Build table rows with links
                if ($lists) {
                    foreach ($lists as &$list) {
                        $listUrl = $this->factory->getRouter()->generate('mautic_leadlist_action', array('objectAction' => 'edit', 'objectId' => $list['id']));
                        $row = array(
                            array(
                                'value' => $list['name'],
                                'type' => 'link',
                                'link' => $listUrl
                            ),
                            array(
                                'value' => $list['leads']
                            )
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.dashboard.label.title'),
                        $event->getTranslator()->trans('mautic.lead.leads')
                    ),
                    'bodyItems'   => $items,
                    'raw'         => $lists
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
            return;
        }

        if ($event->getType() == 'top.owners') {

            if (!$canViewOthers) {
                $event->setErrorMessage($translator->trans('mautic.dashboard.missing.permission', array('%section%' => $this->bundle)));
                $event->stopPropagation();
                return;
            }

            if (!$event->isCached()) {
                $model  = $this->factory->getModel('lead');
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the list limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $owners = $model->getTopOwners($limit, $params['dateFrom'], $params['dateTo']);
                $items = array();

                // Build table rows with links
                if ($owners) {
                    foreach ($owners as &$owner) {
                        $ownerUrl = $this->factory->getRouter()->generate('mautic_user_action', array('objectAction' => 'edit', 'objectId' => $owner['owner_id']));
                        $row = array(
                            array(
                                'value' => $owner['first_name'] . ' ' . $owner['last_name'],
                                'type' => 'link',
                                'link' => $ownerUrl
                            ),
                            array(
                                'value' => $owner['leads']
                            )
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.user.account.permissions.editname'),
                        $event->getTranslator()->trans('mautic.lead.leads')
                    ),
                    'bodyItems'   => $items,
                    'raw'         => $owners
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
            return;
        }

        if ($event->getType() == 'top.creators') {

            if (!$canViewOthers) {
                $event->setErrorMessage($translator->trans('mautic.dashboard.missing.permission', array('%section%' => $this->bundle)));
                $event->stopPropagation();
                return;
            }

            if (!$event->isCached()) {
                $model  = $this->factory->getModel('lead');
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the list limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $creators = $model->getTopCreators($limit, $params['dateFrom'], $params['dateTo']);
                $items = array();

                // Build table rows with links
                if ($creators) {
                    foreach ($creators as &$creator) {
                        $creatorUrl = $this->factory->getRouter()->generate('mautic_user_action', array('objectAction' => 'edit', 'objectId' => $creator['created_by']));
                        $row = array(
                            array(
                                'value' => $creator['created_by_user'],
                                'type' => 'link',
                                'link' => $creatorUrl
                            ),
                            array(
                                'value' => $creator['leads']
                            )
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.user.account.permissions.editname'),
                        $event->getTranslator()->trans('mautic.lead.leads')
                    ),
                    'bodyItems'   => $items,
                    'raw'         => $creators
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
            return;
        }

        if ($event->getType() == 'created.leads') {
            if (!$event->isCached()) {
                $model  = $this->factory->getModel('lead');
                $params = $event->getWidget()->getParams();

                if (empty($params['limit'])) {
                    // Count the leads limit from the widget height
                    $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                } else {
                    $limit = $params['limit'];
                }

                $leads = $model->getLeadList($limit, $params['dateFrom'], $params['dateTo'], $canViewOthers, array(), array('canViewOthers' => $canViewOthers));
                $items = array();

                // Build table rows with links
                if ($leads) {
                    foreach ($leads as &$lead) {
                        $leadUrl = $this->factory->getRouter()->generate('mautic_lead_action', array('objectAction' => 'view', 'objectId' => $lead['id']));
                        $row = array(
                            array(
                                'value' => $lead['name'],
                                'type' => 'link',
                                'link' => $leadUrl
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
                    'raw'         => $leads
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
            return;
        }
    }
}
