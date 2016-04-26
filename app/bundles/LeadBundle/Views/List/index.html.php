<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'leadlist');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.lead.list.header.index'));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'templateButtons' => array(
        'new' => true // this is intentional. Each user can segment leads
    ),
    'routeBase' => 'leadlist',
    'langVar'   => 'lead.list'
)));
?>

<div class="panel panel-default bdr-t-wdh-0">
    <?php echo $view->render('MauticCoreBundle:Helper:list_toolbar.html.php', array(
        'searchValue' => $searchValue,
        'searchHelp'  => 'mautic.lead.list.help.searchcommands',
        'action'      => $currentRoute,
        'langVar'     => 'lead.list',
        'routeBase'   => 'leadlist',
        'templateButtons' => array(
            'delete' => $permissions['lead:lists:deleteother']
        )
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
