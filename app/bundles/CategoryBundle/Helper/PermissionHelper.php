<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Helper;

use Symfony\Component\Form\FormBuilderInterface;

class PermissionHelper
{
    /**
     * Makes sure the user has view access to categories if they have view access to the main entity type
     *
     * @param       $bundleName
     * @param       $entityType
     * @param array $permissions
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