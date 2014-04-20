<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\EventListener;


use Mautic\CoreBundle\Event as MauticEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\DashboardBundle\EventListener
 */
class DashboardSubscriber implements EventSubscriberInterface
{

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            'mautic.build_menu' => array('onBuildMenu', 0),
            'mautic.build_route' => array('onBuildRoute', 0)
        );
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu(MauticEvent\MenuEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/menu.php";
        $items = include $path;
        $event->addMenuItems($items);
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildRoute(MauticEvent\RouteEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/routing.php";
        $event->addRoutes($path);
    }

}