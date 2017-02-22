<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="row" id="companyFieldsContainer">
    <?php if (!empty($specialInstructions)): ?>
        <div class="alert alert-<?php echo $alertType; ?>">
            <?php echo $view['translator']->trans($specialInstructions); ?>
        </div>
    <?php endif; ?>
    <div class="company-field form-group col-xs-12">
        <div class="row">
            <div class="col-sm-1"></div>
            <div class="col-sm-4"><h4><?php echo $view['translator']->trans('mautic.plugins.integration.fields'); ?></h4></div>
            <div class="col-sm-3"><h4><?php echo $view['translator']->trans('mautic.plugins.mautic.direction'); ?></h4></div>
            <div class="col-sm-4"><h4><?php echo $view['translator']->trans('mautic.plugins.mautic.fields'); ?></h4></div>
        </div>
        <?php echo $view['form']->errors($form); ?>
        <?php $rowCount = 0; $indexCount = 1; ?>
        <?php foreach ($form->children as $child): ?>
            <?php if ($rowCount % 3 == 0): ?>
                <div id="company-<?php echo $rowCount; ?>" class="row <?php if ($rowCount > 0 && empty($child->vars['value'])) {
    echo 'hide';
} elseif (($rowCount == 0 && count($form->vars['data']) == 0) || $indexCount == count($form->vars['data'])) {
    echo 'active';
}
                ?>">
                <div class="col-sm-1">
                <span class="btn btn-xs btn-default remove-company-field" onclick="Mautic.removePluginField('company-<?php echo $rowCount; ?>');">
                    <i class="fa fa-close"></i>
                </span>
                </div>
            <?php endif; ?>
            <?php ++$rowCount; ?>
            <div class="col-sm-<?php if ($rowCount % 3 == 2) {
                    echo 3;
                } else {
                    echo 4;
                } ?>">
                <?php echo $view['form']->row($child); ?>
            </div>
            <?php if ($rowCount % 3 == 0):
                $indexCount++;
                ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($rowCount % 3 == 1):?>
    </div>

    <?php endif; ?>
    <a href="#" class="add btn btn-warning btn-xs btn-add-item" onclick="Mautic.addNewPluginField('company-field');">Add field</a>
    </div>
</div>