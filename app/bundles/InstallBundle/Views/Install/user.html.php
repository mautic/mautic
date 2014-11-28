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
?>

<h2 class="page-header">
	<?php echo $view['translator']->trans('mautic.install.heading.user.configuration'); ?>
</h2>

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


        <div class="row">
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
    <div class="col-sm-12">
        <?php echo $view['form']->row($form['buttons']); ?>
    </div>
</div>

<?php echo $view['form']->end($form); ?>