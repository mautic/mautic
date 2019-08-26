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
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.dashboard.header.index'));
$view['slots']->set('mauticContent', 'dashboard');

$buttons = [
    [
        'attr' => [
            'class'       => 'btn btn-default btn-nospin',
            'data-toggle' => 'ajaxmodal',
            'data-target' => '#MauticSharedModal',
            'href'        => $view['router']->path('mautic_dashboard_action', ['objectAction' => 'new']),
            'data-header' => $view['translator']->trans('mautic.dashboard.widget.add'),
        ],
        'iconClass' => 'fa fa-plus',
        'btnText'   => 'mautic.dashboard.widget.add',
    ],
    [
        'attr' => [
            'class'       => 'btn btn-default btn-nospin',
            'href'        => 'javascript:void()',
            'onclick'     => "Mautic.saveDashboardLayout('{$view['translator']->trans('mautic.dashboard.confirmation_layout_name')}');",
            'data-toggle' => '',
        ],
        'iconClass' => 'fa fa-save',
        'btnText'   => 'mautic.core.form.save',
    ],
    [
        'attr' => [
            'class'       => 'btn btn-default btn-nospin',
            'href'        => 'javascript:void()',
            'onclick'     => "Mautic.exportDashboardLayout('{$view['translator']->trans('mautic.dashboard.confirmation_layout_name')}', '{$view['router']->path('mautic_dashboard_action', ['objectAction' => 'export'])}');",
            'data-toggle' => '',
        ],
        'iconClass' => 'fa fa-cloud-download',
        'btnText'   => 'mautic.dashboard.export.widgets',
    ],
    [
        'attr' => [
            'class'       => 'btn btn-default',
            'href'        => $view['router']->path('mautic_dashboard_action', ['objectAction' => 'import']),
            'data-header' => $view['translator']->trans('mautic.dashboard.widget.import'),
        ],
        'iconClass' => 'fa fa-cloud-upload',
        'btnText'   => 'mautic.dashboard.widget.import',
    ],
];

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
    'routeBase'     => 'dashboard',
    'langVar'       => 'dashboard',
    'customButtons' => $buttons,
]));
?>
<div class="row pt-md pl-md">
    <div class="col-sm-6">
        <?php echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', ['dateRangeForm' => $dateRangeForm]); ?>
    </div>
</div>

<?php if (count($widgets)): ?>
    <div id="dashboard-widgets" class="dashboard-widgets cards">
        <?php foreach ($widgets as $widget): ?>
            <div class="card-flex widget" data-widget-id="<?php echo $widget->getId(); ?>" style="width: <?php echo $widget->getWidth() ? $widget->getWidth().'' : '100' ?>%; height: <?php echo $widget->getHeight() ? $widget->getHeight().'px' : '300px' ?>">
                <?php echo $view->render('MauticDashboardBundle:Widget:detail.html.php', ['widget' => $widget]); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div id="cloned-widgets" class="dashboard-widgets cards"></div>
<?php else: ?>
    <div class="well well col-md-6 col-md-offset-3 mt-md">
        <div class="row">
            <div class="mautibot-image col-xs-3 text-center">
                <img class="img-responsive" style="max-height: 125px; margin-left: auto; margin-right: auto;" src="<?php echo $view['mautibot']->getImage('wave'); ?>" />
            </div>
            <div class="col-xs-9">
                <h4><i class="fa fa-quote-left"></i> <?php echo $view['translator']->trans('mautic.dashboard.nowidgets.tip.header'); ?> <i class="fa fa-quote-right"></i></h4>
                <p class="mt-md"><?php echo $view['translator']->trans('mautic.dashboard.nowidgets.tip'); ?></p>
                <a href="<?php echo $view['router']->path('mautic_dashboard_action', ['objectAction' => 'applyDashboardFile', 'file' => 'default.json']); ?>" class="btn btn-success">
                    Apply the default dashboard
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>
