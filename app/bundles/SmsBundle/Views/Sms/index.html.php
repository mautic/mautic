<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'sms');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.sms.smses'));

?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:list_toolbar.html.php', array(
        'searchValue' => $searchValue,
        'searchHelp'  => 'mautic.sms.help.searchcommands',
        'searchId'    => 'sms-search',
        'action'      => $currentRoute,
        'routeBase'   => 'sms',
        'templateButtons' => array(
            'delete' => $permissions['sms:smses:deleteown'] || $permissions['sms:smses:deleteother']
        ),
        // 'filters'     => $filters // @todo
    )); ?>

    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>

