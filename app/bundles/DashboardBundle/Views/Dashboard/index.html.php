<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.dashboard.header.index'));
$view['slots']->set('mauticContent', 'dashboard');

$buttons[] = array(
    'attr'      => array(
        'class'       => 'btn btn-default btn-nospin',
        'data-toggle' => 'ajaxmodal',
        'data-target' => '#MauticSharedModal',
        'href'        => $view['router']->generate('mautic_dashboard_action', array('objectAction' => 'new')),
        'data-header' => $view['translator']->trans('mautic.dashboard.widget.add'),
    ),
    'iconClass' => 'fa fa-plus',
    'btnText'   => 'mautic.dashboard.widget.add'
);

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'routeBase' => 'dashboard',
    'langVar'   => 'dashboard',
    'customButtons' => $buttons
)));
?>
<div class="box-layout">
    <div class="bg-auto bg-dark-xs">
        <?php echo $view->render('MauticDashboardBundle:Widget:list.html.php', array('widgets' => $widgets)); ?>
    </div>
</div>
