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
$view['slots']->set('pageHeader', 'mautic.install.heading.user.configuration');
?>

<?php echo $view['form']->start($form); ?>

<div class="panel panel-primary">
    <div class="panel-heading pa-10">
        <h6><?php echo $view['translator']->trans('mautic.install.user.introtext'); ?></h6>
    </div>
    <div class="panel-body">

        <div class="row">
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['username']); ?>
            </div>
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['password']); ?>
            </div>
        </div>

        <hr class="text-muted" />

        <div class="row mt-lg">
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['firstname']); ?>
            </div>
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['lastname']); ?>
            </div>
        </div>


        <div class="row">
            <div class="col-sm-12">
                <?php echo $view['form']->row($form['email']); ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-20">
    <div class="col-sm-9">
        <?php echo $view->render('MauticInstallBundle:Install:navbar.html.php', array('step' => $index, 'count' => $count, 'completedSteps' => $completedSteps)); ?>
    </div>
    <div class="col-sm-3">
        <?php echo $view['form']->row($form['buttons']); ?>
    </div>
</div>


<?php echo $view['form']->end($form); ?>