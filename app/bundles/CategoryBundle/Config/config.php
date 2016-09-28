<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes'   => array(
        'main' => array(
            'mautic_category_index'  => array(
                'path'       => '/categories/{bundle}/{page}',
                'controller' => 'MauticCategoryBundle:Category:index',
                'defaults'   => array(
                    'bundle' => 'category'
                )
            ),
            'mautic_category_action' => array(
                'path'       => '/categories/{bundle}/{objectAction}/{objectId}',
                'controller' => 'MauticCategoryBundle:Category:executeCategory',
                'defaults'   => array(
                    'bundle' => 'category'
                )
            )
        )
    ),

    'menu' => array(
        'admin' => array(
            'mautic.category.menu.index' => array(
                'route'     => 'mautic_category_index',
                'access'    => 'category:categories:view',
                'iconClass' => 'fa-folder',
                'id'        => 'mautic_category_index'
            )
        )
    ),

    'services' => array(
        'events' => array(
            'mautic.category.subscriber' => array(
                'class' => 'Mautic\CategoryBundle\EventListener\CategorySubscriber',
                'arguments' => [
                    'mautic.helper.bundle',
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog'
                ]
            )
        ),
        'forms'  => array(
            'mautic.form.type.category'      => array(
                'class'     => 'Mautic\CategoryBundle\Form\Type\CategoryListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'category'
            ),
            'mautic.form.type.category_form' => array(
                'class'     => 'Mautic\CategoryBundle\Form\Type\CategoryType',
                'arguments' => 'mautic.factory',
                'alias'     => 'category_form'
            ),
            'mautic.form.type.category_bundles_form' => array(
                'class'     => 'Mautic\CategoryBundle\Form\Type\CategoryBundlesType',
                'arguments' => 'mautic.factory',
                'alias'     => 'category_bundles_form'
            )
        ),
        'models' =>  array(
            'mautic.category.model.category' => array(
                'class' => 'Mautic\CategoryBundle\Model\CategoryModel',
                'arguments' => array(
                    'request_stack'
                )
            )
        )
    )
);
