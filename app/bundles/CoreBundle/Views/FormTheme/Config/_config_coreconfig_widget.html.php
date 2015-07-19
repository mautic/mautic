<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$fields    = $form->children;
$fieldKeys = array_keys($fields);
$template = '<div class="col-md-6">{content}</div>';
?>

<?php if (count(array_intersect($fieldKeys, array('site_url', 'update_stability', 'cache_path', 'log_path', 'theme', 'image_path')))): ?>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.core.config.header.general'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'site_url', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'webroot', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'update_stability', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'cache_path', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'log_path', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'theme', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'image_path', $template); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (count(array_intersect($fieldKeys, array('default_pagelist', 'timezone', 'locale', 'date_format_full', 'date_format_short', 'date_format_dateonly', 'date_format_timeonly')))): ?>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.core.config.header.defaults'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'default_pagelimit', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'default_timezone', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'locale', $template); ?>
        </div>

        <hr class="text-muted" />

        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'date_format_full', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'date_format_short', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'date_format_dateonly', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'date_format_timeonly', $template); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (count(array_intersect($fieldKeys, array('mailer_from_name', 'mailer_from_email', 'mailer_transport', 'mailer_spool_type')))): ?>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.core.config.header.mail'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'mailer_from_name', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'mailer_from_email', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'mailer_return_path', $template); ?>
        </div>

        <?php if (isset($fields['mailer_from_name']) || isset($fields['mailer_from_email'])): ?>
        <hr class="text-muted" />
        <?php endif; ?>

        <?php if (isset($fields['mailer_transport'])): ?>
        <div class="row">
            <div class="col-sm-6">
                <?php echo $view['form']->row($fields['mailer_transport']); ?>
            </div>
            <div class="col-sm-6 pt-lg mt-3" id="mailerTestButtonContainer" data-hide-on='{"config_coreconfig_mailer_transport":["sendmail","mail"]}'>
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
        </div>

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

<?php if (count(array_intersect($fieldKeys, array('cookie_path')))): ?>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.core.config.header.cookie'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'cookie_path', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'cookie_domain', $template); ?>
        </div>

        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'cookie_secure', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'cookie_httponly', $template); ?>
        </div>

        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'rememberme_key', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'rememberme_lifetime', $template); ?>
        </div>

        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'rememberme_path', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'rememberme_domain', $template); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (count(array_intersect($fieldKeys, array('trusted_hosts', 'trusted_proxies', 'ip_lookup_service', 'transifex_username')))): ?>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.core.config.header.misc'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'trusted_hosts', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'trusted_proxies', $template); ?>
        </div>

        <?php if (isset($fields['trusted_hosts'])): ?>
        <hr class="text-muted" />
        <?php endif; ?>

        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'ip_lookup_service', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'ip_lookup_auth', $template); ?>
        </div>

        <?php if (isset($fields['transifex_username'])): ?>
        <hr class="text-muted" />

        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'transifex_username', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'transifex_password', $template); ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'do_not_track_ips', $template); ?>
        </div>
    </div>
</div>
<?php endif; ?>