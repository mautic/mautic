<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

    <div class="row">
        <?php if ($mailbox != 'general'): ?>
        <div class="col-md-6">
            <?php echo $view['form']->row($form['folder']); ?>
        </div>
        <div class="col-md-6">
            <?php echo $view['form']->row($form['override_settings']); ?>
        </div>

        <?php else: ?>
        <div class="col-md-6">
            <?php echo $view['form']->row($form['address']); ?>
        </div>

        <div class="col-md-6 pt-lg" id="<?php echo $mailbox; ?>TestButtonContainer">
            <div class="button_container">
                <?php echo $view['form']->widget($form['test_connection_button']); ?>
                <span class="fa fa-spinner fa-spin hide"></span>
            </div>
            <div class="help-block"></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($mailbox != 'general'): ?>
    <div class="row">
        <div class="col-md-6">
            <?php echo $view['form']->row($form['address']); ?>
        </div>

        <div class="col-md-6 pt-lg" id="<?php echo $mailbox; ?>TestButtonContainer" data-show-on='{"config_emailconfig_monitored_email_<?php echo $mailbox; ?>_override_settings_1": "checked"}'>
            <div class="button_container">
                <?php echo $view['form']->widget($form['test_connection_button']); ?>
                <span class="fa fa-spinner fa-spin hide"></span>
            </div>
            <div class="help-block"></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-sm-12 col-md-6">
            <?php echo $view['form']->row($form['host']); ?>
        </div>
        <div class="col-sm-4 col-md-2">
            <?php echo $view['form']->row($form['port']); ?>
        </div>
        <?php if (extension_loaded('openssl')) : ?>
        <div class="col-sm-8 col-md-4">
            <?php echo $view['form']->row($form['encryption']); ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?php echo $view['form']->row($form['user']); ?>
        </div>
        <div class="col-md-6">
            <?php echo $view['form']->row($form['password']); ?>
        </div>
    </div>