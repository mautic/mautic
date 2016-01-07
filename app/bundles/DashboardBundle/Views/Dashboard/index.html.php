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

<div id="dashboard-widgets" class="cards">
    <?php if ($widgets): ?>
        <?php foreach ($widgets as $widget): ?>
            <div class="card-flex widget" data-widget-id="<?php echo $widget->getId(); ?>" style="width: <?php echo !empty($widget->getWidth()) ? $widget->getWidth() . '' : '100' ?>%; height: <?php echo !empty($widget->getHeight()) ? $widget->getHeight() . 'px' : '300px' ?>">
                <?php echo $view->render('MauticDashboardBundle:Widget:detail.html.php', array('widget' => $widget)); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
