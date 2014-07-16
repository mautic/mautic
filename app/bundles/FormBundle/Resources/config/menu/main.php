<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
$items = array(
    'name' => array(
        'label'    => '',
        'route'    => '',
        'uri'      => '',
        'attributes' => array(),
        'labelAttributes' => array(),
        'linkAttributes' => array(),
        'childrenAttributes' => array(),
        'extras' => array(),
        'display' => true,
        'displayChildren' => true,
        'children' => array()
    )
);
 */

$items = array(
    'mautic.form.form.menu.index' => array(
        'route'    => 'mautic_form_index',
        'linkAttributes' => array(
            'data-toggle' => 'ajax'
        ),
        'extras'=> array(
            'iconClass' => 'fa-pencil-square-o',
            'routeName' => 'mautic_form_index'
        ),
        'display' => $security->isGranted(array('form:forms:viewown', 'form:forms:viewother'), 'MATCH_ONE') ? true : false,
        'children' => array(
            'mautic.form.form.menu.new' => array(
                'route'    => 'mautic_form_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_form_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.form.form.menu.edit' => array(
                'route'           => 'mautic_form_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_form_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.form.form.menu.view' => array(
                'route'           => 'mautic_form_action',
                'routeParameters' => array(
                    "objectAction"  => "view",
                    "objectId"      => $request->get('objectId')
                ),
                'extras'  => array(
                    'routeName' => 'mautic_form_action|view'
                ),
                'display' => false, //only used for breadcrumb generation
                'children' => array(
                    'mautic.form.result.menu.index' => array(
                        'route'           => 'mautic_form_results',
                        'extras'  => array(
                            'routeName' => 'mautic_form_results'
                        ),
                        'display' => false //only used for breadcrumb generation
                    )
                )
            )
        )
    )
);

return $items;
