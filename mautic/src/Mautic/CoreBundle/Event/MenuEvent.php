<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\SecurityContext;

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

    protected $securityContext;

    protected $mauticSecurity;

    /**
     * @param $securityContext
     * @param $mauticSecurity
     */
    public function __construct(SecurityContext $securityContext, CorePermissions $mauticSecurity)
    {
        $this->security       = $securityContext;
        $this->mauticSecurity = $mauticSecurity;
    }

    /**
     * @return mixed
     */
    public function getSecurityContext()
    {
        return $this->security;
    }

    /**
     * @return mixed
     */
    public function getMauticSecurity()
    {
        return $this->mauticSecurity;
    }

    /**
     * Add items to the menu
     */
    public function addMenuItems(array $items)
    {
        if (isset($items['name']) && ($items['name'] == 'root' || $items['name'] == 'admin')) {
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
