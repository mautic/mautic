<?php

/*
 * @copyright   2022 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$fields = $form->children;
$fieldKeys = array_keys($fields);
$template = '<div class="col-md-6">{content}</div>';
?>



<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.messengerconfig'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'messenger_type', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'messenger_transport', $template); ?>
        </div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'messenger_host', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'messenger_port', $template); ?>
        </div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'messenger_stream', $template); ?>
            <?php echo $view['form']->rowIfExists($fields, 'messenger_auto_setup', $template); ?>
        </div>
        <div class="row">
            <?php echo $view['form']->rowIfExists($fields, 'messenger_tls', $template); ?>
        </div>
        <?php if (isset($fields['messenger_type'])): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.messenger.config.retry_strategy'); ?></h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo $view['form']->rowIfExists($fields, 'messenger_retry_strategy_max_retries', $template); ?>
                            <?php echo $view['form']->rowIfExists($fields, 'messenger_retry_strategy_delay', $template); ?>
                        </div>
                        <div class="col-md-6">
                            <?php echo $view['form']->rowIfExists($fields, 'messenger_retry_strategy_multiplier', $template); ?>
                            <?php echo $view['form']->rowIfExists($fields, 'messenger_retry_strategy_max_delay', $template); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>