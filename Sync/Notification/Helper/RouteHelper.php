<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Notification\Helper;


use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\Routing\Router;

class RouteHelper
{

    /**
     * @var Router
     */
    private $router;

    /**
     * RouteHelper constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $object
     * @param int    $id
     *
     * @return string
     * @throws ObjectNotSupportedException
     */
    public function getRoute(string $object, int $id): string
    {
        return $this->router->generate(
            $this->getObjectRoute($object),
            [
                'objectAction' => 'view',
                'objectId'     => $id,
            ]
        );
    }

    /**
     * @param string $object
     * @param int    $id
     * @param string $linkText
     *
     * @return string
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
     * @throws ObjectNotSupportedException
     */
    public function getRoutes(string $object, array $ids): array
    {
        $routes = [];
        foreach ($ids as $id) {
            $routes[$id] = $this->router->generate(
                $this->getObjectRoute($object),
                [
                    'objectAction' => 'view',
                    'objectId'     => (int) $id,
                ]
            );
        }

        return $routes;
    }

    /**
     * @param string      $object
     * @param array       $ids
     *
     * @return string
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

    /**
     * @param string $object
     *
     * @return string
     * @throws ObjectNotSupportedException
     */
    private function getObjectRoute(string $object): string
    {
        switch ($object) {
            case MauticSyncDataExchange::OBJECT_CONTACT:
                return 'mautic_contact_action';
            case MauticSyncDataExchange::OBJECT_COMPANY:
                return 'mautic_company_action';
            default:
                throw new ObjectNotSupportedException('Mautic', $object);
        }
    }
}