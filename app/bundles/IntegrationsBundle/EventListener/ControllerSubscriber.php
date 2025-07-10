<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IntegrationsHelper $integrationsHelper,
        private ControllerResolverInterface $resolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }

    public function onKernelController(\Symfony\Component\HttpKernel\Event\ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if ('Mautic\PluginBundle\Controller\PluginController::configAction' === $request->get('_controller')) {
            $integrationName = $request->get('name');
            $page            = $request->get('page');

            try {
                $this->integrationsHelper->getIntegration($integrationName);
                $request->attributes->add(
                    [
                        'integration'   => $integrationName,
                        'page'          => $page,
                        '_controller'   => 'Mautic\IntegrationsBundle\Controller\ConfigController::editAction',
                        '_route_params' => [
                            'integration' => $integrationName,
                            'page'        => $page,
                        ],
                    ]
                );

                $controller = $this->resolver->getController($request);
                $event->setController($controller);
            } catch (IntegrationNotFoundException) {
                // Old integration so ignore and let old PluginBundle code handle it
            }
        }
    }
}
