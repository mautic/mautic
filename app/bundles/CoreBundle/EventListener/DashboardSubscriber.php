<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardSubscriber extends MainDashboardSubscriber
{
    public const TYPE_RECENT_ACTIVITY = 'recent.activity';

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
        self::TYPE_RECENT_ACTIVITY => [],
    ];

    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        private AuditLogModel $auditLogModel,
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private CorePermissions $security,
        private EventDispatcherInterface $dispatcher,
        protected ModelFactory $modelFactory
    ) {
    }

    /**
     * Set a widget detail when needed.
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event): void
    {
        if (self::TYPE_RECENT_ACTIVITY !== $event->getType()) {
            return;
        }

        if (!$event->isCached()) {
            $height = $event->getWidget()->getHeight();
            $limit  = (int) round(($height - 80) / 75);
            $logs   = $this->auditLogModel->getLogForObject(null, null, null, $limit);

            // Get names of log's items
            foreach ($logs as $key => &$log) {
                if (!isset($log['bundle'], $log['object'], $log['objectId'])) {
                    continue;
                }

                try {
                    $model = $this->modelFactory->getModel($log['bundle'].'.'.$log['object']);
                    $item  = $model->getEntity($log['objectId']);
                    if (null === $item) {
                        $log['objectName'] = $log['object'].'-'.$log['objectId'];
                    } elseif ($model instanceof FormModel && $model->getNameGetter() && method_exists($item, $model->getNameGetter())) {
                        $log['objectName'] = $item->{$model->getNameGetter()}();

                        if ('lead' === $log['bundle'] && 'mautic.lead.lead.anonymous' === $log['objectName']) {
                            $log['objectName'] = $this->translator->trans('mautic.lead.lead.anonymous');
                        }
                    } else {
                        $log['objectName'] = '';
                    }

                    $routeName = 'mautic_'.$log['bundle'].'_action';
                    if (null !== $item && null !== $this->router->getRouteCollection()->get($routeName)) {
                        $log['route'] = $this->router->generate(
                            $routeName,
                            ['objectAction' => 'view', 'objectId' => $log['objectId']]
                        );
                    } else {
                        $log['route'] = false;
                    }
                } catch (\Exception) {
                    unset($logs[$key]);
                }
            }
            unset($log);

            $iconEvent = new IconEvent($this->security);
            $this->dispatcher->dispatch($iconEvent);
            $event->setTemplateData(['logs' => $logs, 'icons' => $iconEvent->getIcons()]);
        }

        $event->setTemplate('@MauticDashboard/Dashboard/recentactivity.html.twig');
        $event->stopPropagation();
    }
}
