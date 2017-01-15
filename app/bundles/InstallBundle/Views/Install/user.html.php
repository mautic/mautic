<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticInstallBundle:Install:content.html.php');
}
?>

<div class="panel-heading">
    <h2 class="panel-title">
        <?php echo $view['translator']->trans('mautic.install.heading.user.configuration'); ?>
    </h2>
</div>
<div class="panel-body">
    <?php echo $view['form']->start($form); ?>
    <div class="alert alert-mautic">
        <?php echo $view['translator']->trans('mautic.install.user.introtext'); ?>
    </div>
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

    <div class="row mt-20">
        <div class="col-sm-9">
            <?php echo $view->render('MauticInstallBundle:Install:navbar.html.php', ['step' => $index, 'count' => $count, 'completedSteps' => $completedSteps]); ?>
        </div>
        <div class="col-sm-3">
            <?php echo $view['form']->row($form['buttons']); ?>
        </div>
    </div>
    <?php echo $view['form']->end($form); ?>
</div>
