<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\CoreBundle\Event as MauticEvents;

/**
 * Class CoreSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class CommonSubscriber implements EventSubscriberInterface
{
    protected $request;
    protected $templating;
    protected $serializer;
    protected $security;
    protected $securityContext;
    protected $dispatcher;
    protected $factory;
    protected $params;
    protected $translator;


    public function __construct (MauticFactory $factory)
    {
        $this->factory         = $factory;
        $this->templating      = $factory->getTemplating();
        $this->request         = $factory->getRequest();
        $this->security        = $factory->getSecurity();
        $this->securityContext = $factory->getSecurityContext();
        $this->serializer      = $factory->getSerializer();
        $this->params          = $factory->getSystemParameters();
        $this->dispatcher      = $factory->getDispatcher();
        $this->translator      = $factory->getTranslator();
    }

    static public function getSubscribedEvents ()
    {
        return array();
    }

    /**
     * Find and add menu items
     *
     * @param MenuEvent $event
     * @param           $name
     */
    protected function buildMenu(MauticEvents\MenuEvent $event, $name)
    {
        $security = $event->getSecurity();
        $request  = $this->factory->getRequest();

        $bundles = $this->factory->getParameter('bundles');
        $menuItems = array();
        foreach ($bundles as $bundle) {
            //check common place
            $path = $bundle['directory'] . "/Config/menu/$name.php";
            if (!file_exists($path)) {
                if ($name == 'main') {
                    //else check for just a menu.php file
                    $path = $bundle['directory'] . "/Config/menu.php";
                }
                $recheck = true;
            } else {
                $recheck = false;
            }

            if (!$recheck || file_exists($path)) {
                $config      = include $path;
                $menuItems[] = array(
                    'priority' => !isset($config['priority']) ? 9999 : $config['priority'],
                    'items'    => !isset($config['items'])    ? $config : $config['items']
                );
            }
        }

        usort($menuItems, function($a, $b) {
            $ap = $a['priority'];
            $bp = $b['priority'];

            if ($ap == $bp) {
                return 0;
            }

            return ($ap < $bp) ? -1 : 1;
        });

        foreach ($menuItems as $items) {
            $event->addMenuItems($items['items']);
        }
    }

    /**
     * Find and add menu items
     *
     * @param IconEvent $event
     * @param           $name
     */
    protected function buildIcons(MauticEvents\IconEvent $event)
    {
        $security = $event->getSecurity();
        $request  = $this->factory->getRequest();
        $bundles = $this->factory->getParameter('bundles');
        $icons = array();

        foreach ($bundles as $bundle) {
            //check common place
            $path = $bundle['directory'] . "/Config/menu/main.php";
            if (!file_exists($path)) {
                //else check for just a menu.php file
                $path = $bundle['directory'] . "/Config/menu.php";
                $recheck = true;
            } else {
                $recheck = false;
            }

            if (!$recheck || file_exists($path)) {
                $config = include $path;
                $items = (!isset($config['items']) ? $config : $config['items']);
                if ($items) {
                    foreach ($items as $item) {
                        $icons[] = $item;
                        if (isset($item['extras']['iconClass']) && isset($item['linkAttributes']['id'])) {
                            $id = explode('_', $item['linkAttributes']['id']);
                            if (isset($id[1])) {
                                $event->addIcon($id[1], $item['extras']['iconClass']);
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Get routing from bundles and add to Routing event
     *
     * @param $event
     * @param $name
     */
    protected function buildRoute(MauticEvents\RouteEvent $event, $name)
    {
        $bundles = $this->factory->getParameter('bundles');

        $routes = array();
        foreach ($bundles as $bundle) {
            $routing = $bundle['directory'] . "/Config/$name.php";
            if (file_exists($routing)) {
                $event->addRoutes($routing);
            } else {
                $routing = $bundle['directory'] . "/Config/routing/$name.php";
                if (file_exists($routing)) {
                    $event->addRoutes($routing);
                }
            }
        }
    }
}