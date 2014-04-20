<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class MenuEvent
 *
 * @package Mautic\CoreBundle\Event
 */
class MenuEvent extends Event
{
    /**
     * @var
     */
    protected $menuItems = array('children' => array());

    /**
     * Add items to the menu
     */
    public function addMenuItems(array $items)
    {
        if (isset($items['name']) && $items['name'] == 'root') {
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
     * @return mixed
     */
    public function getMenuItems()
    {
        return $this->menuItems;
    }
}
