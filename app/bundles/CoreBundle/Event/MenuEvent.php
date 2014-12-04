<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Menu\MenuHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
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
     * @var User
     */
    protected $user;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param CorePermissions $security
     */
    public function __construct(CorePermissions $security, User $user, Request $request)
    {
        $this->security = $security;
        $this->user     = $user;
        $this->request  = $request;
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
            MenuHelper::createMenuStructure($items);
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
     * @return Request
     */
    public function getRequest ()
    {
        return $this->request;
    }

    /**
     * @return User
     */
    public function getUser ()
    {
        return $this->user;
    }
}
