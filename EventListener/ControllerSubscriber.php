<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\EventListener;

use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Helper\IntegrationsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerSubscriber implements EventSubscriberInterface
{
    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * ControllerSubscriber constructor.
     *
     * @param IntegrationsHelper $integrationsHelper
     */
    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        $request    = $event->getRequest();

        if ('Mautic\PluginBundle\Controller\PluginController::configAction' === $request->get('_controller')) {
            $integrationName = $request->attributes->get('_route_params')['name'];

            try {
                $integrationObject = $this->integrationsHelper->getIntegration($integrationName);

                // This is a new integration so let's "hijack" the controller
                $event->setController('IntegrationsBundle:Config:edit');
                $request->attributes->set('_controller', 'MauticPlugin\IntegrationsBundle\Controller\ConfigController::editAction');
                $request->attributes->set('_route_params',
                    [
                        'integration' => $integrationName,
                        'page'        => $request->attributes->get('_route_params')['page'],
                    ]
                );
            } catch (IntegrationNotFoundException $exception) {
                // Old integration so ignore and let old PluginBundle code handle it
            }
        }
    }
}
