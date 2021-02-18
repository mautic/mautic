<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ('index' === $tmpl) {
    $view->extend('MauticInstallBundle:Install:content.html.php');
}
?>

<div class="panel-heading">
    <h2 class="panel-title">
        <?php echo $view['translator']->trans('mautic.install.heading.email.configuration'); ?>
    </h2>
</div>
<div class="panel-body">
    <?php echo $view['form']->start($form); ?>
    <h4 class="mb-10"><?php echo $view['translator']->trans('mautic.install.email.header.emailfrom'); ?> <i class="ml-5 fa fa-info-circle" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.install.email.subheader.emailfrom'); ?>"></i></h4>

    <div class="row">
        <div class="col-sm-6">
            <?php echo $view['form']->row($form['mailer_from_name']); ?>
        </div>
        <div class="col-sm-6">
            <?php echo $view['form']->row($form['mailer_from_email']); ?>
        </div>
    </div>

    <h4 class="mb-10 mt-10">
        <?php echo $view['translator']->trans('mautic.install.email.header.spooler'); ?> <i class="ml-5 fa fa-info-circle" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.install.email.subheader.spooler'); ?>"></i>
    </h4>
    <div class="row">
        <div class="col-sm-6">
            <?php echo $view['form']->row($form['mailer_spool_type']); ?>
        </div>

        <div class="col-sm-6">
            <?php echo $view['form']->row($form['mailer_transport']); ?>
        </div>
    </div>
    <div id="smtpSettings">
        <div class="row">
            <div class="col-sm-9">
                <?php echo $view['form']->row($form['mailer_host']); ?>
            </div>
            <div class="col-sm-3">
                <?php echo $view['form']->row($form['mailer_port']); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['mailer_encryption']); ?>
            </div>
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['mailer_auth_mode']); ?>
            </div>
        </div>
    </div>
    <div id="amazonRegion">
        <div class="row">
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['mailer_amazon_region']); ?>
            </div>
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['mailer_amazon_other_region']); ?>
            </div>
        </div>
    </div>
    <div id="authDetails">
        <div class="row">
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['mailer_user']); ?>
            </div>
            <div class="col-sm-6">
                <?php echo $view['form']->row($form['mailer_password']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $view['form']->row($form['mailer_api_key']); ?>
            </div>
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
    <script>
        Mautic.initializeFormFieldVisibilitySwitcher('install_email_step');
    </script>
</div>
