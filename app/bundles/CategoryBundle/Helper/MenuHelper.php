<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;

class MenuHelper
{
    /**
     * Adds the category menu items to a bundle's menu
     *
     * @param $items
     * @param $bundleName
     * @param $security
     */
    public static function addCategoryMenuItems(&$items, $bundleName, CorePermissions $security)
    {
        $items['mautic.category.menu.index'] = array(
            'route'    => 'mautic_category_index',
            'routeParameters' => array("bundle"  => $bundleName),
            'extras'  => array(
                'routeName' => 'mautic_category_index'
            ),
            'linkAttributes' => array(
                'data-toggle' => 'ajax'
            ),
            'display' => $security->isGranted($bundleName . ':categories:view') ? true : false,
            'children' => array(
                'mautic.category.menu.new' => array(
                    'route'    => 'mautic_category_action',
                    'routeParameters' => array(
                        "objectAction"  => "new",
                        "bundle"        => $bundleName
                    ),
                    'extras'  => array(
                        'routeName' => 'mautic_category_action|'.$bundleName.'|new'
                    ),
                    'display' => false //only used for breadcrumb generation
                ),
                'mautic.category.menu.edit' => array(
                    'route'           => 'mautic_category_action',
                    'routeParameters' => array(
                        "objectAction"  => "edit",
                        "bundle"        => $bundleName
                    ),
                    'extras'  => array(
                        'routeName' => 'mautic_category_action|'.$bundleName.'|edit'
                    ),
                    'display' => false //only used for breadcrumb generation
                )
            )
        );
    }
}