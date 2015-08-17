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

<?php // Email specific settings moved to EmailBundle to prevent confusion due to tab names ?>

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