<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;


use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Event\RouteEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CoreSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class CoreSubscriber implements EventSubscriberInterface
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
            'mautic.build_menu' => array('onBuildMenu', 9999),
            'mautic.build_route' => array('onBuildRoute', 0)
        );
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu(MenuEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/menu.php";
        $items = include $path;
        $event->addMenuItems($items);
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildRoute(RouteEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/routing.php";
        $event->addRoutes($path);
    }
}