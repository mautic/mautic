<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s).
     *
     * @var string
     */
    protected $bundle = 'core';

    /**
     * Define the widget(s).
     *
     * @var string
     */
    protected $types = [
        'recent.activity' => [],
    ];

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ModelFactory
     */
    protected $modelFactory;

    /**
     * @param AuditLogModel       $auditLogModel
     * @param TranslatorInterface $translator
     * @param AuditLogModel       $router
     * @param ModelFactory        $modelFactory
     */
    public function __construct(
        AuditLogModel $auditLogModel,
        TranslatorInterface $translator,
        Router $router,
        ModelFactory $modelFactory
    ) {
        $this->auditLogModel = $auditLogModel;
        $this->translator    = $translator;
        $this->router        = $router;
        $this->modelFactory  = $modelFactory;
    }

    /**
     * Set a widget detail when needed.
     *
     * @param WidgetDetailEvent $event
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event)
    {
        if ($event->getType() == 'recent.activity') {
            if (!$event->isCached()) {
                $height = $event->getWidget()->getHeight();
                $limit  = round(($height - 80) / 75);
                $logs   = $this->auditLogModel->getLogForObject(null, null, null, $limit);

                // Get names of log's items
                foreach ($logs as $key => &$log) {
                    if (!empty($log['bundle']) && !empty($log['object']) && !empty($log['objectId'])) {
                        try {
                            $model = $this->modelFactory->getModel($log['bundle'].'.'.$log['object']);
                            $item  = $model->getEntity($log['objectId']);
                            if (method_exists($item, $model->getNameGetter())) {
                                $log['objectName'] = $item->{$model->getNameGetter()}();

                                if ($log['bundle'] == 'lead' && $log['objectName'] == 'mautic.lead.lead.anonymous') {
                                    $log['objectName'] = $this->translator->trans('mautic.lead.lead.anonymous');
                                }
                            } else {
                                $log['objectName'] = '';
                            }

                            $routeName = 'mautic_'.$log['bundle'].'_action';
                            if ($this->router->getRouteCollection()->get($routeName) !== null) {
                                $log['route'] = $this->router->generate(
                                    'mautic_'.$log['bundle'].'_action',
                                    ['objectAction' => 'view', 'objectId' => $log['objectId']]
                                );
                            } else {
                                $log['route'] = false;
                            }
                        } catch (\Exception $e) {
                            unset($logs[$key]);
                        }
                    }
                }

                $iconEvent = new IconEvent($this->security);
                $this->dispatcher->dispatch(CoreEvents::FETCH_ICONS, $iconEvent);
                $event->setTemplateData(['logs' => $logs, 'icons' => $iconEvent->getIcons()]);
            }

            $event->setTemplate('MauticDashboardBundle:Dashboard:recentactivity.html.php');
            $event->stopPropagation();
        }
    }
}
