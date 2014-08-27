<?php
$columnList = $form->vars['columnList'];
$selectedColumns = $form->vars['data'];

$labelClass = (empty($form->vars['label_attr']['class'])) ? 'control-label' : $form->vars['label_attr']['class'];
?>
<div class="row">
    <div class="form-group col-xs-12 col-sm-8 col-md-6">
        <label class="<?php echo $labelClass; ?>"><?php echo $view['translator']->trans($form->vars['label']); ?></label>
        <div class="row">
            <div class="choice-wrapper col-xs-5">
                <label class="<?php echo $labelClass; ?>" for="<?php echo $form->vars['id'] . '_available'; ?>"><?php echo $view['translator']->trans('mautic.report.report.label.availablecolumns'); ?></label>
                <select id="<?php echo $form->vars['id'] . '_available'; ?>" name="<?php echo $form->vars['id'] . '[available]'; ?>" class="form-control" multiple="multiple" size="5">
                    <?php foreach ($columnList as $column) { ?>
                    <?php if (!in_array($column, $selectedColumns)) { ?>
                    <option value="<?php echo $column; ?>"><?php echo $column; ?></option>
                    <?php } ?>
                    <?php } ?>
                </select>
            </div>
            <div class="col-xs-2">
                <div class="text-center" style="margin-bottom: 10px; padding-top: 18px;">
                    <button id="column-move-left" class="btn btn-sm btn-default"><i class="fa fa-caret-square-o-left"></i></button>
                </div>
                <div class="text-center">
                    <button id="column-move-right" class="btn btn-sm btn-default"><i class="fa fa-caret-square-o-right"></i></button>
                </div>
            </div>
            <div class="choice-wrapper col-xs-5">
                <label class="<?php echo $labelClass; ?>" for="<?php echo $form->vars['id']; ?>"><?php echo $view['translator']->trans('mautic.report.report.label.selectedcolumns'); ?></label>
                <select id="<?php echo $form->vars['id']; ?>" name="<?php echo $form->vars['full_name']; ?>" class="form-control" multiple="multiple" size="5">
                    <?php foreach ($selectedColumns as $column) { ?>
                    <option value="<?php echo $column; ?>"><?php echo $column; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
    </div>
</div>
