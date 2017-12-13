<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('headerTitle', 'Manage Fields');

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
    'templateButtons' => [
        //'new' => $permissions[$permissionBase.':create'],
        'new' => true
    ],
    'route' => 'plugin_unsubscribe_new_campaign_name',
    'query' => ['bundle' => $bundle, 'show_bundle_select' => true],
    'editMode' => 'ajaxmodal',
    'editAttr' => [
        'data-target' => '#MauticSharedModal',
        'data-header' => $view['translator']->trans('plugin.unsubscribe.campaign_name.header.new'),
        'data-toggle' => 'ajaxmodal',
    ],
]));

?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>