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
        <?php $rowCount = 0; $indexCount = 1; $numberOfFields = ($form->offsetExists('update_mautic_company1')) ? 3 : 2; ?>
        <div class="row">
            <div class="mb-xs ml-lg pl-xs pr-xs col-sm-4 text-center"><h4><?php echo $view['translator']->trans('mautic.plugins.integration.fields'); ?></h4></div>
            <?php if ($numberOfFields == 3): ?>
            <div class="pl-xs pr-xs col-sm-2"></div>
            <?php endif; ?>
            <div class="pl-xs pr-xs col-sm-4 text-center"><h4><?php echo $view['translator']->trans('mautic.plugins.mautic.fields'); ?></h4></div>
            <div class="pl-xs pr-xs col-sm-1 text-center"></div>
        </div>
        <?php echo $view['form']->errors($form); ?>

        <?php foreach ($form->children as $child): ?>
            <?php if ($rowCount % $numberOfFields == 0): ?>
                <div id="company-<?php echo $rowCount; ?>" class="row<?php if ($rowCount > 0 && empty($child->vars['value'])) {
    echo ' hide';
} ?>">
                <?php endif; ?>
                    <?php ++$rowCount; ?>
                    <div class="<?php if ($rowCount % $numberOfFields == 1) {
    echo 'ml-lg';
} ?> pl-xs pr-xs col-sm-<?php if ($rowCount % $numberOfFields == 2) {
    echo '2 ml-xs';
} else {
    echo '4';
} ?>">
                        <?php echo $view['form']->row($child); ?>
                    </div>
                    <?php if ($rowCount % $numberOfFields == 0):
                            $indexCount++;
                    ?>
                    <div class="pl-xs pr-xs col-sm-1">
                        <span class="btn btn-default remove-company-field" onclick="Mautic.removePluginField('company-field', 'company-<?php echo $rowCount - $numberOfFields; ?>');">
                            <span class="fa fa-close"></span>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($rowCount % $numberOfFields == 1):?>
    </div>

    <?php endif; ?>
    <a href="#" class="add btn btn-warning btn-xs btn-add-item" onclick="Mautic.addNewPluginField('company-field');">Add field</a>
    </div>
</div>