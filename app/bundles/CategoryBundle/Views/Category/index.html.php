<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'category');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.category.header.index'));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
    'templateButtons' => [
       'new' => $permissions[$permissionBase.':create'],
    ],
    'routeBase' => 'category',
    'query'     => ['bundle' => $bundle, 'show_bundle_select' => true],
    'editMode'  => 'ajaxmodal',
    'editAttr'  => [
        'data-target' => '#MauticSharedModal',
        'data-header' => $view['translator']->trans('mautic.category.header.new'),
        'data-toggle' => 'ajaxmodal',
    ],
]));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php //TODO - Restore these buttons to the listactions when custom content is supported
    /*<div class="btn-group">
        <button type="button" class="btn btn-default"><i class="fa fa-upload"></i></button>
        <button type="button" class="btn btn-default"><i class="fa fa-archive"></i></button>
    </div>*/ ?>
    <?php echo $view->render('MauticCoreBundle:Helper:list_toolbar.html.php', [
        'searchValue' => $searchValue,
        'searchHelp'  => 'mautic.category.help.searchcommands',
        'filters'     => [
            'bundle' => [
                'options'         => $categoryTypes,
                'values'          => [$bundle],
                'translateLabels' => true,
            ],
        ],
        'action' => $currentRoute,
    ]); ?>

    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
