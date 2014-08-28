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
     * Adds the category permissions to the permission array
     *
     * @param $permissions
     */
    public static function addCategoryPermissions(&$permissions)
    {
        $permissions['categories'] = array(
            'view'          => 1,
            'edit'          => 4,
            'create'        => 8,
            'delete'        => 32,
            'publish'       => 64,
            'full'          => 1024
        );
    }

    /**
     * Adds category permissions to the permission form builder
     *
     * @param                      $permissionBundle
     * @param FormBuilderInterface $builder
     * @param array                $data
     */
    public static function buildForm ($permissionBundle, FormBuilderInterface &$builder, array $data)
    {
        $builder->add($permissionBundle.':categories', 'button_group', array(
            'choices'    => array(
                'view'    => 'mautic.core.permissions.view',
                'edit'    => 'mautic.core.permissions.edit',
                'create'  => 'mautic.core.permissions.create',
                'publish' => 'mautic.core.permissions.publish',
                'delete'  => 'mautic.core.permissions.delete',
                'full'    => 'mautic.core.permissions.full'
            ),
            'label'      => 'mautic.permissions.categories',
            'label_attr' => array('class' => 'control-label'),
            'expanded'   => true,
            'multiple'   => true,
            'attr'       => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \''. $permissionBundle . '\')'
            ),
            'data'      => (!empty($data['categories']) ? $data['categories'] : array())
        ));
    }


    /**
     * Gets synonyms for category permissions. I.e. viewown and viewother should be just view
     *
     * @param $name
     * @param $level
     * @return array
     */
    public static function getSynonym($level) {
        //set some synonyms
        switch ($level) {
            case "viewown":
            case "viewother":
                $level = "view";
                break;
            case "editown":
            case "editother":
                $level = "edit";
                break;
            case "deleteown":
            case "deleteother":
                $level = "delete";
                break;
            case "publishown":
            case "publishother":
                $level = "publish";
                break;
        }

        return $level;
    }

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