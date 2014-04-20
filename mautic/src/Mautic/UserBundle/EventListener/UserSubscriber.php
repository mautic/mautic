<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\EventListener;


use Mautic\CoreBundle\Event as MauticEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserSubscriber
 *
 * @package Mautic\UserBundle\EventListener
 */
class UserSubscriber implements EventSubscriberInterface
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
            'mautic.build_menu'     => array('onBuildMenu', 9997),
            'mautic.build_route'    => array('onBuildRoute', 0),
            'mautic.global_search'  => array('onGlobalSearch', 0)
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

    public function onGlobalSearch(MauticEvent\GlobalSearchEvent $event)
    {
        if ($this->container->get('mautic.security')->isGranted('user:users:view')) {
            $str   = $event->getSearchString();
            $users = $this->container->get('mautic.model.user')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));

            if (count($users) > 0) {
                $userResults = array();
                $canEdit     = $this->container->get('mautic.security')->isGranted('user:users:edit');
                foreach ($users as $user) {
                    $userResults[] = $this->container->get('templating')->renderResponse(
                        'MauticUserBundle:Search:user.html.php',
                        array(
                            'user'    => $user,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($users) > 5) {
                    $userResults[] = $this->container->get('templating')->renderResponse(
                        'MauticUserBundle:Search:user.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($users) - 5)
                        )
                    )->getContent();
                }
                $event->addResults('mautic.user.user.header.index', $userResults);
            }
        }

        if ($this->container->get('mautic.security')->isGranted('user:roles:view')) {
            $str   = $event->getSearchString();
            $roles = $this->container->get('mautic.model.role')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));
            if (count($roles)) {
                $roleResults = array();
                $canEdit     = $this->container->get('mautic.security')->isGranted('user:roles:edit');

                foreach ($roles as $role) {
                    $roleResults[] = $this->container->get('templating')->renderResponse(
                        'MauticUserBundle:Search:role.html.php',
                        array(
                            'role'    => $role,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($roles) > 5) {
                    $roleResults[] = $this->container->get('templating')->renderResponse(
                        'MauticUserBundle:Search:role.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($roles) - 5)
                        )
                    )->getContent();
                }
                $event->addResults('mautic.user.role.header.index', $roleResults);
            }
        }
    }
}