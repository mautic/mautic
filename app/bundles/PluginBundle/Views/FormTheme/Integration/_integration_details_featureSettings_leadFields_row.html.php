<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="row" id="leadFieldsContainer">
    <?php if (!empty($specialInstructions)): ?>
        <div class="alert alert-<?php echo $alertType; ?>">
            <?php echo $view['translator']->trans($specialInstructions); ?>
        </div>
    <?php endif; ?>

    <div class="field form-group col-xs-12">
        <div class="row">
            <div class="col-sm-1"></div>
            <div class="col-sm-3"><?php echo $view['translator']->trans('mautic.plugins.integration.fields'); ?></div>
            <div class="col-sm-3"><?php echo $view['translator']->trans('mautic.plugins.mautic.direction'); ?></div>
            <div class="col-sm-3"><?php echo $view['translator']->trans('mautic.plugins.mautic.fields'); ?></div>
        </div>
        <?php echo $view['form']->errors($form); ?>

        <?php $rowCount = 1; $indexCount = 1; ?>

        <?php foreach ($form->children as $child): ?>
        <?php if ($rowCount++ % 2 == 1): ?>
                <?php if ((isset($child->vars['data']) && !empty($child->vars['data']))) {
    $integrationField = $child->vars['data'];
}?>
        <div id="<?php echo $rowCount; ?>" class="row <?php if ($rowCount > 2 && (!isset($child->vars['data']) || empty($child->vars['data']))) {
    echo 'hide';
} elseif ((!isset($child->vars['data']) || empty($child->vars['data'])) || $indexCount == count($child->vars['data'])) {
    echo 'active';
}?>">
                <div class="col-sm-1">
                <span class="btn btn-xs btn-default removeField" onclick="Mautic.addNewPluginField();">
                    <i class="fa fa-close"></i>
                </span>
                </div>
            <?php else: ?>
                <?php if ((isset($child->vars['data']) && !empty($child->vars['data']))) {
    $mauticField = $child->vars['data'];
} ?>
            <?php endif; ?>

            <div class="col-sm-3">
                <?php echo $view['form']->row($child); ?>
            </div>
        <?php if ($rowCount++ % 2 == 1): ?>


                </div>
        <?php endif; ?>
        <?php ++$rowCount; ?>
            <?php
            if ($rowCount++ % 2 == 1 && $rowCount > 1):
            ?>
                <div id="m_i_<?php echo $indexCount; ++$indexCount; ?>" class="hide">
            <?php if (isset($integrationField)): ?>
                <input type="hidden" id="integration_details_featureSettings_leadFields_<?php echo $integrationField ?>" name="integration_details[featureSettings][leadFields][<?php echo $mauticField; ?>]" value="<?php echo $mauticField; ?>">
            <?php endif; ?>
            <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php
        if ($rowCount % 2 == 0):?>
    </div>

        <?php endif; ?>
    <a href="#" class="add btn btn-warning btn-xs btn-add-item" onclick="Mautic.addNewPluginField();">Add field</a>
    </div>
</div>