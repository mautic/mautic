<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Helper;

class PermissionHelper
{
    /**
     * Makes sure the user has view access to categories if they have view access to the main entity type
     *
     * @param string $bundleName
     * @param string $entityType
     * @param array  $permissions
     *
     * @return void
     */
    public static function analyzePermissions ($bundleName, $entityType, array &$permissions)
    {
        $permissions     = (isset($permissions["$bundleName:$entityType"])) ? $permissions["$bundleName:$entityType"] : array();
        $catPermissions  = (isset($permissions["$bundleName:categories"])) ? $permissions["$bundleName:categories"] : array();
        //make sure the user has access to view categories if they have access to view forms
        if ((isset($permissions['viewown']) || isset($permissions['viewother']))
            && !isset($catPermissions['view'])) {
            $permissions["$bundleName:categories"][] = 'view';
        }
    }
}
