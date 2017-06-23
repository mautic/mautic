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

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.queue_settings'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <?php echo $view['form']->row($fields['queue_protocol']); ?>
            </div>
        </div>

        <?php foreach ($fields as $fieldname => $field): ?>
            <?php if ($fieldname === 'queue_protocol') {
    continue;
} ?>
            <div class="row">
                <?php echo $view['form']->rowIfExists($fields, $fieldname, $template); ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
