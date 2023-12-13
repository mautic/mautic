<?php

namespace Mautic\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\Matcher\MatcherInterface;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MenuBuilder
{
    /**
     * @var \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    private $dispatcher;

    public function __construct(
        private FactoryInterface $factory,
        private MatcherInterface $matcher,
        EventDispatcherInterface $dispatcher,
        private MenuHelper $menuHelper
    ) {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $name = str_replace('Menu', '', $name);

        return $this->buildMenu($name);
    }

    /**
     * Used by breadcrumbs to determine active link.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param string                  $forRouteUri
     * @param string                  $forRouteName
     *
     * @return \Knp\Menu\ItemInterface|null
     */
    public function getCurrentMenuItem($menu, $forRouteUri, $forRouteName)
    {
        try {
            /** @var \Knp\Menu\ItemInterface $item */
            foreach ($menu as $item) {
                if ('current' == $forRouteUri && $this->matcher->isCurrent($item)) {
                    // current match
                    return $item;
                } elseif ('current' != $forRouteUri && $item->getUri() == $forRouteUri) {
                    // route uri match
                    return $item;
                } elseif (!empty($forRouteName) && $forRouteName == $item->getExtra('routeName')) {
                    // route name match
                    return $item;
                }

                if ($item->getChildren() && $current_child = $this->getCurrentMenuItem($item, $forRouteUri, $forRouteName)) {
                    return $current_child;
                }
            }
        } catch (\Exception) {
            // do nothing
        }

        return null;
    }

    /**
     * @return mixed
     */
    private function buildMenu($name)
    {
        static $menus = [];

        if (!isset($menus[$name])) {
            $loader = new ArrayLoader($this->factory);

            // dispatch the MENU_BUILD event to retrieve bundle menu items
            $event = new MenuEvent($this->menuHelper, $name);
            $this->dispatcher->dispatch($event, CoreEvents::BUILD_MENU);

            $menuItems    = $event->getMenuItems();

            // KNP Menu explicitly requires a menu name since v3
            if (empty($menuItems['name'])) {
                $menuItems['name'] = $name;
            }
            $menus[$name] = $loader->load($menuItems);
        }

        return $menus[$name];
    }
}
