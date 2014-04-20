<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;


use Mautic\CoreBundle\Event as MauticEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ApiSubscriber
 *
 * @package Mautic\ApiBundle\EventListener
 */
class ApiSubscriber implements EventSubscriberInterface
{

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct (ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            'mautic.build_menu'    => array('onBuildMenu', 9998),
            'mautic.build_route'   => array('onBuildRoute', 0),
            'mautic.global_search' => array('onGlobalSearch', 0)
        );
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu (MauticEvent\MenuEvent $event)
    {
        $path  = __DIR__ . "/../Resources/config/menu.php";
        $items = include $path;
        $event->addMenuItems($items);
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildRoute (MauticEvent\RouteEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/routing.php";
        $event->addRoutes($path);
    }

    /**
     * @param GlobalSearchEvent $event
     */
    public function onGlobalSearch (MauticEvent\GlobalSearchEvent $event)
    {
        if ($this->container->get('mautic.security')->isGranted('api:clients:view')) {
            $str     = $event->getSearchString();
            $clients = $this->container->get('mautic.model.client')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));

            if (count($clients) > 0) {
                $userResults = array();
                $canEdit     = $this->container->get('mautic.security')->isGranted('api:clients:edit');
                foreach ($clients as $client) {
                    $userResults[] = $this->container->get('templating')->renderResponse(
                        'MauticApiBundle:Search:client.html.php',
                        array(
                            'client'  => $client,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($clients) > 5) {
                    $userResults[] = $this->container->get('templating')->renderResponse(
                        'MauticApiBundle:Search:client.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($clients) - 5)
                        )
                    )->getContent();
                }
                $event->addResults('mautic.api.client.header.index', $userResults);
            }
        }

    }
}