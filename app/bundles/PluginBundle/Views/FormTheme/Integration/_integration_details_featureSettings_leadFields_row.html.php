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
        <div class="col-sm-2"></div>
        <div class="col-sm-5"><?php echo $view['translator']->trans('mautic.plugins.integration.fields'); ?></div>
        <div class="col-sm-5"><?php echo $view['translator']->trans('mautic.plugins.mautic.fields'); ?></div>
        <?php echo $view['form']->errors($form); ?>

        <?php $rowCount = 1; $indexCount = 1; ?>
        <?php foreach ($form->children as $child): ?>
        <?php if ($rowCount++ % 2 == 1): ?>
        <div id="<?php echo $rowCount; ?>" class="row <?php if ($rowCount > 2) {
    echo 'hide';
} else {
    echo 'active';
}?>">
                <div class="col-sm-2">
                <span class="btn btn-xs btn-default remove" onclick="Mautic.addNewPluginField();">
                    <i class="fa fa-times"></i>
                </span>
                </div>

            <?php endif; ?>


            <div class="col-sm-5">
                <?php echo $view['form']->row($child); ?>
            </div>
        <?php if ($rowCount++ % 2 == 1): ?>

                <div id="m_i_<?php echo $indexCount; ++$indexCount; ?>"></div>
                </div>
        <?php endif; ?>
        <?php ++$rowCount; ?>
        <?php endforeach; ?>
        <?php
        if ($rowCount % 2 == 0):?>
    </div>

        <?php endif; ?>
    <a href="#" class="add btn btn-warning btn-xs btn-add-item" onclick="Mautic.addNewPluginField();">Add field</a>
    </div>
</div>