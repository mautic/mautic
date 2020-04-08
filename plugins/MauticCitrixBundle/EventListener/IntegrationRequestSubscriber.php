<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\EventListener;

use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class StatsSubscriber.
 */
class IntegrationRequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST => [
                'getParameters',
                0,
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function getParameters(PluginIntegrationRequestEvent $requestEvent)
    {
        if (false !== strpos($requestEvent->getUrl(), 'oauth/v2/token')) {
            $authorization = $this->getAuthorization($requestEvent->getParameters());
            $requestEvent->setHeaders([
                'Authorization' => sprintf('Basic %s', base64_encode($authorization)),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ]);
        }
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    private function getAuthorization(array $parameters)
    {
        if (empty($parameters['client_id'])) {
            throw new \Exception('No client ID given.');
        }

        if (empty($parameters['client_secret'])) {
            throw new \Exception('No client secret given.');
        }

        return sprintf('%s:%s', $parameters['client_id'], $parameters['client_secret']);
    }
}
