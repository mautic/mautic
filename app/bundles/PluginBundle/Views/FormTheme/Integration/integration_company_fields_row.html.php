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
    <div class="form-group col-xs-12">
        <?php echo $view['form']->errors($form); ?>
        <?php $rowCount = 0; ?>
        <?php foreach ($form->children as $child): ?>
            <?php if ($rowCount % 3 == 0): ?>
                <div id="<?php echo $rowCount; ?>" class="row <?php if ($rowCount > 4 && (!isset($child->vars['data']) || empty($child->vars['data']))) {
    echo 'hide';
} elseif ((!isset($child->vars['data']) || empty($child->vars['data'])) || $indexCount == count($child->vars['data'])) {
    echo 'active';
}?>">
                <div class="col-sm-1">
                <span class="btn btn-xs btn-default removeField" onclick="Mautic.addNewPluginField();">
                    <i class="fa fa-close"></i>
                </span>
                </div>
            <?php endif; ?>
            <?php ++$rowCount; ?>
            <div class="col-sm-3">
                <?php echo $view['form']->row($child); ?>
            </div>
            <?php if ($rowCount % 3 == 0): ?>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>
        <?php if ($rowCount % 3 == 1):?>
    </div>

    <?php endif; ?>
    </div>
</div>