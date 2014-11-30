<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', $application);
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.mapper.clients.title'));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'templateButtons' => array(
        'new' => $permissions[$application.':mapper:create']
    ),
    'query'     => array("application" => $application),
    'routeBase' => 'mapper_client',
    'langVar'   => 'mapper.client'
)));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php //TODO - Restore these buttons to the listactions when custom content is supported
    /*<div class="btn-group">
        <button type="button" class="btn btn-default"><i class="fa fa-upload"></i></button>
        <button type="button" class="btn btn-default"><i class="fa fa-archive"></i></button>
    </div>*/ ?>
    <?php echo $view->render('MauticCoreBundle:Helper:bulk_actions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'langVar'     => 'mapper.client',
        'routeBase'   => 'mapper_client',
        'templateButtons' => array(
            'delete' => $permissions[$application . ':mapper:delete'],
        ),
        'query'       => array(
            'application' => $application
        )
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
