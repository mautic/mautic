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
 */
class CommonSubscriber implements EventSubscriberInterface
{

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    protected $templating;

    /**
     * @var \JMS\Serializer\Serializer
     */
    protected $serializer;

    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    protected $security;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContext
     */
    protected $securityContext;

    /**
     * @var \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    protected $translator;

    /**
     * @param MauticFactory $factory
     */
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array();
    }

    /**
     * Find and add menu items
     *
     * @param MauticEvents\MenuEvent $event
     * @param string                 $name
     *
     * @return void
     */
    protected function buildMenu(MauticEvents\MenuEvent $event, $name)
    {
        $security = $event->getSecurity();
        $request  = $this->factory->getRequest();

        $bundles   = $this->factory->getParameter('bundles');
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
                    'items'    => !isset($config['items']) ? $config : $config['items']
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
     * @param MauticEvents\IconEvent $event
     *
     * @return void
     */
    protected function buildIcons(MauticEvents\IconEvent $event)
    {
        $security = $event->getSecurity();
        $request  = $this->factory->getRequest();
        $bundles  = $this->factory->getParameter('bundles');
        $icons    = array();

        foreach ($bundles as $bundle) {
            //check common place
            $path = $bundle['directory'] . "/Config/menu/main.php";
            if (!file_exists($path)) {
                //else check for just a menu.php file
                $path    = $bundle['directory'] . "/Config/menu.php";
                $recheck = true;
            } else {
                $recheck = false;
            }

            if (!$recheck || file_exists($path)) {
                $config = include $path;
                $items  = (!isset($config['items']) ? $config : $config['items']);
                if ($items) {
                    foreach ($items as $item) {
                        $icons[] = $item;
                        if (isset($item['extras']['iconClass']) && isset($item['linkAttributes']['id'])) {
                            $id = explode('_', $item['linkAttributes']['id']);
                            if (isset($id[1])) {
                                // some bundle names are in plural, create also singular item
                                if (substr($id[1], -1) == 's') {
                                    $event->addIcon(rtrim($id[1], 's'), $item['extras']['iconClass']);
                                }
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
     * @param MauticEvents\RouteEvent $event
     * @param string                  $name
     *
     * @return void
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
