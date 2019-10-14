<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Notification\Helper;

use MauticPlugin\IntegrationsBundle\Event\InternalObjectRouteEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RouteHelper
{
    /**
     * @var ObjectProvider
     */
    private $objectProvider;

    /**
     * @var RouEventDispatcherInterfaceter
     */
    private $dispatcher;

    /**
     * @param ObjectProvider           $objectProvider
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ObjectProvider $objectProvider,
        EventDispatcherInterface $dispatcher
    ) {
        $this->objectProvider = $objectProvider;
        $this->dispatcher     = $dispatcher;
    }

    /**
     * @param string $object
     * @param int    $id
     *
     * @return string
     *
     * @throws ObjectNotSupportedException
     */
    public function getRoute(string $object, int $id): string
    {
        try {
            $event = new InternalObjectRouteEvent($this->objectProvider->getObjectByName($object), $id);
        } catch (ObjectNotFoundException $e) {
            // Throw this exception instead to keep BC.
            throw new ObjectNotSupportedException(MauticSyncDataExchange::NAME, $object);
        }

        $this->dispatcher->dispatch(IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE, $event);

        return $event->getRoute();
    }

    /**
     * @param string $object
     * @param int    $id
     * @param string $linkText
     *
     * @return string
     *
     * @throws ObjectNotSupportedException
     */
    public function getLink(string $object, int $id, string $linkText): string
    {
        $route = $this->getRoute($object, $id);

        return sprintf('<a href="%s">%s</a>', $route, $linkText);
    }

    /**
     * @param string $object
     * @param array  $ids
     *
     * @return array
     *
     * @throws ObjectNotSupportedException
     */
    public function getRoutes(string $object, array $ids): array
    {
        $routes = [];
        foreach ($ids as $id) {
            $routes[$id] = $this->getRoute($object, $id);
        }

        return $routes;
    }

    /**
     * @param string $object
     * @param array  $ids
     *
     * @return string
     *
     * @throws ObjectNotSupportedException
     */
    public function getLinkCsv(string $object, array $ids): string
    {
        $links  = [];
        $routes = $this->getRoutes($object, $ids);
        foreach ($routes as $id => $route) {
            $links[] = sprintf('[<a href="%s">%s</a>]', $route, $id);
        }

        return implode(', ', $links);
    }
}
