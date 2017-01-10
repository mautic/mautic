<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$fields    = $form->children;
$fieldKeys = array_keys($fields);
$template  = '<div class="col-md-6">{content}</div>';
?>

<?php if (count(array_intersect($fieldKeys, ['mailer_from_name', 'mailer_from_email', 'mailer_transport', 'mailer_spool_type']))): ?>
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.email.config.header.mail'); ?></h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, 'mailer_from_name', $template); ?>
                <?php echo $view['form']->rowIfExists($fields, 'mailer_from_email', $template); ?>
            </div>
            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, 'mailer_return_path', $template); ?>
                <?php echo $view['form']->rowIfExists($fields, 'mailer_is_owner', $template); ?>
            </div>

            <?php if (isset($fields['mailer_from_name']) || isset($fields['mailer_from_email'])): ?>
                <hr class="text-muted" />
            <?php endif; ?>

            <?php if (isset($fields['mailer_transport'])): ?>
                <div class="row">
                    <div class="col-sm-6">
                        <?php echo $view['form']->row($fields['mailer_transport']); ?>
                    </div>
                    <div class="col-sm-6 pt-lg mt-3" id="mailerTestButtonContainer" data-hide-on='{"config_emailconfig_mailer_transport":["sendmail","mail"]}'>
                        <div class="button_container">
                            <?php echo $view['form']->widget($fields['mailer_test_connection_button']); ?>
                            <?php echo $view['form']->widget($fields['mailer_test_send_button']); ?>
                            <span class="fa fa-spinner fa-spin hide"></span>
                        </div>
                        <div class="col-md-9 help-block"></div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, 'mailer_amazon_region', $template); ?>
            </div>

            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, 'mailer_host', $template); ?>
                <?php echo $view['form']->rowIfExists($fields, 'mailer_port', $template); ?>
            </div>

            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, 'mailer_encryption', $template); ?>
                <?php echo $view['form']->rowIfExists($fields, 'mailer_auth_mode', $template); ?>
            </div>

            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, 'mailer_user', $template); ?>
                <?php echo $view['form']->rowIfExists($fields, 'mailer_password', $template); ?>
                <?php echo $view['form']->rowIfExists($fields, 'mailer_api_key', $template); ?>
            </div>

            <?php if (isset($fields['mailer_transport'])): ?>
                <div class="row">
                    <?php echo $view['form']->rowIfExists($fields, 'mailer_mailjet_sandbox', $template); ?>
                    <?php echo $view['form']->rowIfExists($fields, 'mailer_mailjet_sandbox_default_mail', $template); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($fields['mailer_transport'])): ?>
                <hr class="text-muted" />
            <?php endif; ?>

            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, 'mailer_spool_type', $template); ?>
                <?php echo $view['form']->rowIfExists($fields, 'mailer_spool_path', $template); ?>
            </div>

            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, 'mailer_spool_msg_limit', $template); ?>
                <?php echo $view['form']->rowIfExists($fields, 'mailer_spool_time_limit', $template); ?>
            </div>

            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, 'mailer_spool_recover_timeout', $template); ?>
                <?php echo $view['form']->rowIfExists($fields, 'mailer_spool_clear_timeout', $template); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.frequency_rules'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <?php echo $view['form']->row($fields['email_frequency_number']); ?>
            </div>
            <div class="col-md-12">
                <?php echo $view['form']->row($fields['email_frequency_time']); ?>
            </div>
        </div>
    </div>
</div>

<?php if (isset($fields['monitored_email'])): ?>
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.email.config.header.monitored_email'); ?></h3>
        </div>
        <div class="panel-body">
            <?php if (function_exists('imap_open')): ?>
                <?php echo $view['form']->widget($form['monitored_email']); ?>
            <?php else: ?>
                <div class="alert alert-info"><?php echo $view['translator']->trans('mautic.email.imap_extension_missing'); ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.email.config.header.message'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'webview_text', $template); ?>
        </div>

        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'default_signature_text', $template); ?>
        </div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'mailer_append_tracking_pixel', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'mailer_convert_embed_images', $template); ?>
        </div>
    </div>
</div>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.email.config.header.unsubscribe'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'unsubscribe_text', $template); ?>
        </div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'unsubscribe_message', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'resubscribe_message', $template); ?>
        </div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'unsubscribe_message', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'resubscribe_message', $template); ?>
        </div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'show_contact_preferences', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'show_contact_segments', $template); ?>
        </div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'show_contact_frequency', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'show_contact_pause_dates', $template); ?>
        </div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'show_contact_categories', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'show_contact_preferred_channels', $template); ?>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.email.config.header.queue'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="alert alert-info"><?php echo $view['translator']->trans('mautic.email.config.alert.queue'); ?></div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'track_mail_use_queue', $template); ?>
        </div>
    </div>
</div>
