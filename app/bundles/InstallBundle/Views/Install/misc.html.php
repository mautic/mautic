<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticInstallBundle:Install:content.html.php');
}

$view['slots']->set('pageHeader', 'mautic.install.heading.misc.configuration');
?>
<?php echo $view['form']->start($form); ?>
<div class="panel panel-primary">
    <div class="panel-heading pa-10">
        <h4><?php echo $view['translator']->trans('mautic.install.misc.header.url'); ?></h4>
        <h6><?php echo $view['translator']->trans('mautic.install.misc.subheader.url'); ?></h6>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-12">
                <?php echo $view['form']->row($form['site_url']); ?>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading pa-10">
        <h4><?php echo $view['translator']->trans('mautic.install.misc.header.paths'); ?></h4>
        <h6><?php echo $view['translator']->trans('mautic.install.misc.subheader.paths'); ?></h6>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['log_path']); ?>
            </div>
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['cache_path']); ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-20">
    <div class="col-sm-9">
        <div class="hide" id="waitMessage">
            <div class="alert alert-info">
                <strong><?php echo $view['translator']->trans('mautic.install.finalizing'); ?></strong>
            </div>
        </div>
        <?php echo $view->render('MauticInstallBundle:Install:navbar.html.php', array('step' => $index, 'count' => $count, 'completedSteps' => $completedSteps)); ?>
    </div>
    <div class="col-sm-3">
        <?php echo $view['form']->row($form['buttons']); ?>
    </div>
</div>

<?php echo $view['form']->end($form); ?>
