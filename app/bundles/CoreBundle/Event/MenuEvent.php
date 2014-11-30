<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class MenuEvent
 */
class MenuEvent extends Event
{

    /**
     * @var array
     */
    protected $menuItems = array('children' => array());

    /**
     * @var CorePermissions
     */
    protected $security;

    /**
     * @param CorePermissions $security
     */
    public function __construct(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * @return CorePermissions
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * Add items to the menu
     *
     * @param array $items
     *
     * @return void
     */
    public function addMenuItems(array $items)
    {
        $isRoot = isset($items['name']) && ($items['name'] == 'root' || $items['name'] == 'admin');
        if (!$isRoot) {
            $this->createMenuStructure($items);
        }

        if ($isRoot) {
            //make sure the root does not override the children
            if (isset($this->menuItems['children'])) {
                if (isset($items['children'])) {
                    $items['children'] = array_merge_recursive($this->menuItems['children'], $items['children']);
                } else {
                    $items['children'] = $this->menuItems['children'];
                }
            }
            $this->menuItems = $items;
        } else {
            $this->menuItems['children'] = array_merge_recursive($this->menuItems['children'], $items);
        }
    }

    /**
     * Return the menu items
     *
     * @return array
     */
    public function getMenuItems()
    {
        return $this->menuItems;
    }

    /**
     * Converts menu config into something KNP menus expects
     *
     * @param $items
     */
    private function createMenuStructure(&$items)
    {
        foreach ($items as &$i) {

            //Set ID to route name
            if (!isset($i['id']) && isset($i['route'])) {
                $i['id'] = $i['route'];
            }

            //Set link attributes
            $i['linkAttributes'] = array(
                'data-menu-link' => $i['id'],
                'id'             => $i['id']
            );

            $i['extras'] = array();

            //Set the icon class for the menu item
            if (!empty($i['iconClass'])) {
                $i['extras']['iconClass'] = $i['iconClass'];
            }

            //Set the actual route name so that it's available to the menu template
            if (isset($i['route'])) {
                $i['extras']['routeName'] = $i['route'];
            }

            //Repeat for sub items
            if (isset($i['children'])) {
                $this->createMenuStructure($i['children']);
            }
        }
    }
}
