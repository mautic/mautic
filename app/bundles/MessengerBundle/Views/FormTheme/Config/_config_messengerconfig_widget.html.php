<?php

/*
 * @copyright   2022 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$fields    = $form->children;
$fieldKeys = array_keys($fields);
//we want to show these keys no matter what is the transport selected
$retryKeys = ['messenger_retry_strategy_max_retries', 'messenger_retry_strategy_delay', 'messenger_retry_strategy_multiplier', 'messenger_retry_strategy_max_delay'];
$template  = '<div class="col-md-6">{content}</div>';
?>



<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.messengerconfig'); ?></h3>
    </div>
    <div class="panel-body">
    <div class='row'>
    <?php
        $i = 0;
foreach ($fieldKeys as $key) {
    if (in_array($key, $retryKeys)) {
        continue;
    }
    echo $view['form']->rowIfExists($fields, $key, $template);
}
?>
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