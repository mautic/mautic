<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\CoreEvents;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'core';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'recent.activity' => array()
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
        if ($event->getType() == 'recent.activity') {
            if (!$event->isCached()) {
                $model  = $this->factory->getModel('core.auditLog');
                $height = $event->getWidget()->getHeight();
                $limit  = round(($height - 80) / 75);
                $logs   = $model->getLogForObject(null, null, null, $limit);

                // Get names of log's items
                $router = $this->factory->getRouter();
                foreach ($logs as $key => &$log) {
                    if (!empty($log['bundle']) && !empty($log['object']) && !empty($log['objectId'])) {
                        try {
                            $model = $this->factory->getModel($log['bundle'].'.'.$log['object']);
                            $item  = $model->getEntity($log['objectId']);
                            if (method_exists($item, $model->getNameGetter())) {
                                $log['objectName'] = $item->{$model->getNameGetter()}();

                                if ($log['bundle'] == 'lead' && $log['objectName'] == 'mautic.lead.lead.anonymous') {
                                    $log['objectName'] = $this->factory->getTranslator()->trans('mautic.lead.lead.anonymous');
                                }
                            } else {
                                $log['objectName'] = '';
                            }

                            $routeName = 'mautic_'.$log['bundle'].'_action';
                            if ($router->getRouteCollection()->get($routeName) !== null) {
                                $log['route'] = $router->generate(
                                    'mautic_'.$log['bundle'].'_action',
                                    array('objectAction' => 'view', 'objectId' => $log['objectId'])
                                );
                            } else {
                                $log['route'] = false;
                            }
                        } catch (\Exception $e) {
                            unset($logs[$key]);
                        }
                    }
                }

                $iconEvent = new IconEvent($this->factory->getSecurity());
                $this->factory->getDispatcher()->dispatch(CoreEvents::FETCH_ICONS, $iconEvent);
                $event->setTemplateData(array('logs' => $logs, 'icons' => $iconEvent->getIcons()));
            }

            $event->setTemplate('MauticDashboardBundle:Dashboard:recentactivity.html.php');
            $event->stopPropagation();
        }
    }
}
