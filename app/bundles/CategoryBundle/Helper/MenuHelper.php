<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;

class MenuHelper
{
    /**
     * Adds the category menu items to a bundle's menu.
     *
     * @param $items
     * @param $bundleName
     * @param $security
     */
    public static function addCategoryMenuItems(&$items, $bundleName, CorePermissions $security)
    {
        if (!$security->isGranted($bundleName.':categories:view')) {
            return;
        }

        $items['mautic.category.menu.index'] = [
            'route'           => 'mautic_category_index',
            'id'              => "mautic_{$bundleName}category_index",
            'routeParameters' => ['bundle' => $bundleName],
        ];
    }
}
