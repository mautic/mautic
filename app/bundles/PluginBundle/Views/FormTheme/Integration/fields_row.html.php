<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var int $numberOfFields */
$rowCount   = 0;
$indexCount = 1;
?>

<div class="row fields-container" id="<?php echo $containerId; ?>">
    <?php if (!empty($specialInstructions)): ?>
        <div class="alert alert-<?php echo $alertType; ?>">
            <?php echo $view['translator']->trans($specialInstructions); ?>
        </div>
    <?php endif; ?>
    <div class="<?php echo $object; ?>-field form-group col-xs-12">
        <div class="row">
            <div class="mb-xs ml-lg pl-xs pr-xs col-sm-4 text-center"><h4><?php echo $view['translator']->trans('mautic.plugins.integration.fields'); ?></h4></div>
            <?php if ($numberOfFields == 4): ?>
                <div class="pl-xs pr-xs col-sm-2"></div>
            <?php endif; ?>
            <div class="pl-xs pr-xs col-sm-4 text-center"><h4><?php echo $view['translator']->trans('mautic.plugins.mautic.fields'); ?></h4></div>
            <div class="pl-xs pr-xs col-sm-1 text-center"></div>
        </div>
        <?php echo $view['form']->errors($form); ?>
        <?php foreach ($form->children as $child): ?>
            <?php $isRequired = !empty($child->vars['attr']['data-required']); ?>
            <?php if ($rowCount % $numberOfFields == 0):  ?>
            <div id="<?php echo $object; ?>-<?php echo $rowCount; ?>" class="field-container row<?php echo ($rowCount > 0 && !$isRequired && empty($child->vars['attr']['data-matched'])) ? ' hide' : ''; ?>">
            <?php endif; ?>
            <?php ++$rowCount; ?>
            <?php
            if ('hidden' === $child->vars['block_prefixes'][1]):
                echo $view['form']->row($child);
            else:
            ?>
            <div class="<?php echo ($rowCount % $numberOfFields == 1) ? 'ml-lg ' : ''; ?>pl-xs pr-xs col-sm-<?php echo ($rowCount % $numberOfFields == 2) ? '2 ml-xs' : '4'; ?>"
                 <?php if ($rowCount % $numberOfFields == 2): ?>data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.plugin.direction.data.update'); ?>"<?php endif; ?>>
                <?php
                if ($isRequired && $rowCount % $numberOfFields == 1):
                    $name                            = $child->vars['full_name'];
                    $child->vars['full_name']        = $child->vars['id'];
                    $child->vars['attr']['disabled'] = 'disabled';
                    echo '<input type="hidden" value="'.$child->vars['value'].'" name="'.$name.'" />';
                endif;

                echo $view['form']->row($child);
                ?>
            </div>
            <?php endif; ?>
            <?php if ($rowCount % $numberOfFields == 0): ?>
                <div class="pl-xs pr-xs col-sm-1">
                    <button type="button" class="btn btn-default remove-field"
                            onclick="Mautic.removePluginField('<?php echo $object; ?>-field','<?php echo $object; ?>-<?php echo $rowCount - $numberOfFields; ?>');"
                            <?php if ($isRequired): ?>disabled<?php endif; ?>>
                        <span class="fa fa-close"></span>
                    </button>
                </div>
                </div>
                <?php
                ++$indexCount;
            endif; ?>
        <?php endforeach; ?>
        <a href="#" class="add btn btn-warning ml-sm btn-add-item" onclick="Mautic.addNewPluginField('<?php echo $object; ?>-field', null);">
            <?php echo $view['translator']->trans('mautic.plugin.form.add.fields'); ?>
        </a>
    </div>
</div>