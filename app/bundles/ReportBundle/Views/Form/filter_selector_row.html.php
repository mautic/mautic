<?php
$conditionArray = array('=', '>', '>=', '<', '<=', '!=');

$columnList = $form->vars['columnList'];
$setFilters = $form->vars['data'];

$labelClass = (empty($form->vars['label_attr']['class'])) ? 'control-label' : $form->vars['label_attr']['class'];
?>
<div class="row">
    <div class="form-group col-xs-12 col-sm-8 col-md-6">
        <label class="<?php echo $labelClass; ?>"><?php echo $view['translator']->trans($form->vars['label']); ?></label>
        <?php foreach ($setFilters as $key => $filter) { ?>
        <div class="row">
            <div class="choice-wrapper col-xs-4">
                <label class="<?php echo $labelClass; ?>" for="report_filter_rule_1"><?php echo $view['translator']->trans('mautic.report.report.label.filtercolumn'); ?></label>
                <select id="<?php echo $form->vars['id'] . '_' . $key . '_column'; ?>" name="<?php echo $form->vars['full_name'] . '[' . $key . '][column]'; ?>" class="form-control">
                    <?php foreach ($columnList as $column) { ?>
                    <option value="<?php echo $column; ?>"<?php echo ($column == $filter['column']) ? ' selected' : ''; ?>><?php echo $column; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="choice-wrapper col-xs-2">
                <label class="<?php echo $labelClass; ?>" for="report_filter_condition_1"><?php echo $view['translator']->trans('mautic.report.report.label.filtercondition'); ?></label>
                <select id="<?php echo $form->vars['id'] . '_' . $key . '_condition'; ?>" name="<?php echo $form->vars['full_name'] . '[' . $key . '][condition]'; ?>" class="form-control">
                    <?php foreach ($conditionArray as $condition) { ?>
                    <option value="<?php echo $condition; ?>"<?php echo ($condition == $filter['condition']) ? ' selected' : '' ?>><?php echo $condition; ?></option>
                    <?php } ?>
                    <option value="=">=</option>
                    <option value=">">&gt;</option>
                    <option value=">=" selected="selected">&gt;=</option>
                    <option value="<">&lt;</option>
                    <option value="<=">&lt;=</option>
                    <option value="!=">!=</option>
                </select>
            </div>
            <div class="choice-wrapper col-xs-4">
                <div class="form-group">
                    <label class="<?php echo $labelClass; ?>" for="report_filter_value_1"><?php echo $view['translator']->trans('mautic.report.report.label.filtervalue'); ?></label>
                    <input type="text" id="<?php echo $form->vars['id'] . '_' . $key . '_value'; ?>" name="<?php echo $form->vars['full_name'] . '[' . $key . '][value]'; ?>" required="required" class="form-control" value="<?php echo $filter['value']; ?>" />
                </div>
            </div>
            <div class="col-xs-2">
                <label class="<?php echo $labelClass; ?>"><?php echo $view['translator']->trans('mautic.report.report.label.removefilter'); ?></label>
                <button class="btn btn-sm btn-danger"><i class="fa fa-minus-square-o"></i></button>
            </div>
        </div>
        <?php } ?>
    </div>
    <div class="col-xs-12">
        <button class="btn btn-primary"><?php echo $view['translator']->trans('mautic.report.report.label.addfilter'); ?></button>
    </div>
</div>
