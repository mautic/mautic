<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Menu\MenuHelper;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MenuEvent.
 */
class MenuEvent extends Event
{
    /**
     * @var array
     */
    protected $menuItems = ['children' => []];

    /**
     * @var
     */
    protected $type;

    /**
     * Menu helper.
     *
     * @var MenuHelper
     */
    protected $helper;

    /**
     * MenuEvent constructor.
     *
     * @param MenuHelper $menuHelper
     * @param string     $type
     */
    public function __construct(MenuHelper $menuHelper, $type = 'main')
    {
        $this->helper = $menuHelper;
        $this->type   = $type;
    }

    /**
     * @param array $menuItems
     */
    public function setMenuItems(array $menuItems)
    {
        $this->menuItems = $menuItems;
    }

    /**
     * Add items to the menu.
     *
     * @param array $menuItems
     */
    public function addMenuItems(array $menuItems)
    {
        $defaultPriority = isset($menuItems['priority']) ? $menuItems['priority'] : 9999;
        $items           = isset($menuItems['items']) ? $menuItems['items'] : $menuItems;

        $isRoot = isset($items['name']) && ($items['name'] == 'root' || $items['name'] == $items['name']);
        if (!$isRoot) {
            $this->helper->createMenuStructure($items, 0, $defaultPriority, $this->type);

            $this->menuItems['children'] = array_merge_recursive($this->menuItems['children'], $items);
        } else {
            //make sure the root does not override the children
            if (isset($this->menuItems['children'])) {
                if (isset($items['children'])) {
                    $items['children'] = array_merge_recursive($this->menuItems['children'], $items['children']);
                } else {
                    $items['children'] = $this->menuItems['children'];
                }
            }
            $this->menuItems = $items;
        }
    }

    /**
     * Return the menu items.
     *
     * @return array
     */
    public function getMenuItems()
    {
        $this->helper->placeOrphans($this->menuItems['children'], true, 1, $this->type);
        $this->helper->sortByPriority($this->menuItems['children']);
        $this->helper->resetOrphans($this->type);

        return $this->menuItems;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
