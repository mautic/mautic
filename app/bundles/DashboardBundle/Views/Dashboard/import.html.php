<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'dashboardImport');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.dashboard.import'));
?>

<div class="row">
    <div class="col-sm-offset-2 col-sm-8">
        <div class="ml-lg mr-lg mt-md pa-lg">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title"><?php echo $view['translator']->trans('mautic.dashboard.import.start.instructions'); ?></div>
                </div>
                <div class="panel-body">
                    <?php echo $view['form']->start($form); ?>
                    <div class="input-group well mt-lg">
                        <?php echo $view['form']->widget($form['file']); ?>
                        <span class="input-group-btn">
                            <?php echo $view['form']->widget($form['start']); ?>
                        </span>
                    </div>
                    <?php echo $view['form']->end($form); ?>
                </div>
            </div>

        <?php if ($dashboards) : ?>
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title"><?php echo $view['translator']->trans('mautic.dashboard.predefined'); ?></div>
                </div>
                <div class="panel-body">
                    <?php foreach ($dashboards as $dashboard) : ?>
                        <?php echo $dashboard; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>
<?php if (!empty($widgets)) : ?>
    <div class="col-md-12">
        <h2><?php echo $view['translator']->trans('mautic.dashboard.widgets.preview'); ?></h2>
    </div>
    <div id="dashboard-widgets" class="cards">
        <?php if ($widgets): ?>
            <?php foreach ($widgets as $widget): ?>
                <div class="card-flex widget" data-widget-id="<?php echo $widget->getId(); ?>" style="width: <?php echo !empty($widget->getWidth()) ? $widget->getWidth() . '' : '100' ?>%; height: <?php echo !empty($widget->getHeight()) ? $widget->getHeight() . 'px' : '300px' ?>">
                    <?php echo $view->render('MauticDashboardBundle:Widget:detail.html.php', array('widget' => $widget)); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
