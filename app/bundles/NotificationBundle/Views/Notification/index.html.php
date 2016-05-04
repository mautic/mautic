<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'notification');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.notification.notifications'));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:list_toolbar.html.php', array(
        'searchValue' => $searchValue,
        'searchHelp'  => 'mautic.notification.help.searchcommands',
        'searchId'    => 'notification-search',
        'action'      => $currentRoute,
        'routeBase'   => 'notification',
        'templateButtons' => array(
            'delete' => $permissions['notification:notifications:deleteown'] || $permissions['notification:notifications:deleteother']
        ),
        //'filters'     => $filters //@todo
    )); ?>

    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>

