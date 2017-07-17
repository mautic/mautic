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
     *
     * @deprecated - to be removed in 3.0; no longer used
     */
    public static function addCategoryMenuItems(&$items, $bundleName, CorePermissions $security)
    {
        @trigger_error('Individual category menu items are no longer used.', E_USER_DEPRECATED);
    }
}
