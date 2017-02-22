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

        <?php $rowCount = 0; $indexCount = 1; ?>

        <?php foreach ($form->children as $child): ?>
        <?php if ($rowCount % 3 == 0):   ?>
        <div id="<?php echo $rowCount; ?>" class="row <?php if ($rowCount > 0 && empty($child->vars['value'])) {
    echo 'hide';
} elseif ($rowCount == 0 || $rowCount == count($form->vars['data']) * 2) {
    echo 'active';
}?>">
                <div class="col-sm-1">
                <span class="btn btn-xs btn-default removeField" onclick="Mautic.addNewPluginField(<?php echo $rowCount; ?>);">
                    <i class="fa fa-close"></i>
                </span>
                </div>
            <?php  elseif ($rowCount % 3 == 1): ?>

            <?php endif; ?>
<?php ++$rowCount; ?>
            <div class="col-sm-3">
                <?php echo $view['form']->row($child); ?>
            </div>

        <?php if ($rowCount % 3 == 0):
                $indexCount++;
                ?>
                </div>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php
        if ($rowCount % 3 == 1):?>
    </div>

        <?php endif; ?>
    <a href="#" class="add btn btn-warning btn-xs btn-add-item" onclick="Mautic.addNewPluginField();">Add field</a>
    </div>
</div>