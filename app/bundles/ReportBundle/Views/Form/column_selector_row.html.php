<?php
$selectedColumns = $form->vars['value'];

$labelClass = (empty($form->vars['label_attr']['class'])) ? 'control-label' : $form->vars['label_attr']['class'];
?>
<div class="row">
    <div class="form-group col-xs-12 col-sm-8 col-md-6">
        <label class="<?php echo $labelClass; ?>"><?php echo $view['translator']->trans($form->vars['label']); ?></label>
        <div class="row">
            <div class="choice-wrapper col-xs-5">
                <label class="<?php echo $labelClass; ?>" for="<?php echo $form->vars['id'] . '_available'; ?>"><?php echo $view['translator']->trans('mautic.report.report.label.availablecolumns'); ?></label>
                <select id="<?php echo $form->vars['id'] . '_available'; ?>" name="<?php echo $form->vars['id'] . '[available]'; ?>" class="<?php echo $form->vars['attr']['class'] ?>"<?php if ($form->vars['multiple']) echo ' multiple="multiple"'; ?> size="<?php echo $form->vars['attr']['size'] ?>">
                    <?php foreach ($form->vars['choices'] as $choice) { ?>
                    <?php if (!$selectedColumns[$choice->value]) { ?>
                    <option value="<?php echo $choice->value; ?>"><?php echo $choice->label; ?></option>
                    <?php } ?>
                    <?php } ?>
                </select>
            </div>
            <div class="columnSelectorButtons col-xs-1">
                <div class="text-center">
                    <button type="button" id="column-move-left" class="btn btn-sm btn-default btn-nospin" onclick="Mautic.moveReportColumns('<?php echo $form->vars['id']; ?>', '<?php echo $form->vars['id'] . '_available'; ?>');"><i class="fa fa-caret-square-o-left"></i></button>
                </div>
                <div class="text-center">
                    <button type="button" id="column-move-right" class="btn btn-sm btn-default btn-nospin" onclick="Mautic.moveReportColumns('<?php echo $form->vars['id'] . '_available'; ?>', '<?php echo $form->vars['id']; ?>');"><i class="fa fa-caret-square-o-right"></i></button>
                </div>
            </div>
            <div class="choice-wrapper col-xs-5">
                <label class="<?php echo $labelClass; ?>" for="<?php echo $form->vars['id']; ?>"><?php echo $view['translator']->trans('mautic.report.report.label.selectedcolumns'); ?></label>
                <span data-toggle="tooltip" data-container="body" data-placement="top"
                      data-original-title="<?php echo $view['translator']->trans('mautic.report.report.tooltip.selected.columns'); ?>">
                    <i class="fa fa-question-circle"></i>
                </span>
                <select id="<?php echo $form->vars['id']; ?>" name="<?php echo $form->vars['full_name']; ?>[]" class="<?php echo $form->vars['attr']['class'] ?>"<?php if ($form->vars['multiple']) echo ' multiple="multiple"'; ?> size="<?php echo $form->vars['attr']['size'] ?>">
                    <?php foreach ($form->vars['choices'] as $choice) { ?>
                    <?php if ($selectedColumns[$choice->value]) { ?>
                    <option value="<?php echo $choice->value; ?>"><?php echo $choice->label; ?></option>
                    <?php } ?>
                    <?php } ?>
                </select>
            </div>
            <div class="columnSelectorButtons col-xs-1">
                <div class="text-center">
                    <button type="button" id="column-move-left" class="btn btn-sm btn-default btn-nospin" onclick="Mautic.reorderColumns('<?php echo $form->vars['id']; ?>', 'up');"><i class="fa fa-caret-square-o-up"></i></button>
                </div>
                <div class="text-center">
                    <button type="button" id="column-move-right" class="btn btn-sm btn-default btn-nospin" onclick="Mautic.reorderColumns('<?php echo $form->vars['id']; ?>', 'down');"><i class="fa fa-caret-square-o-down"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
