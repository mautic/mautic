<?php
$conditionArray = array('=', '>', '>=', '<', '<=', '!=');
$columnList = $form->vars['columnList'];
$setFilters = $form->vars['value'];

$labelClass = (empty($form->vars['label_attr']['class'])) ? 'control-label' : $form->vars['label_attr']['class'];
?>
<div id="<?php echo $form->vars['id']; ?>_container" class="row">
    <?php echo $view['form']->row($form->vars['form']->children['column']); ?>
    <?php echo $view['form']->row($form->vars['form']->children['condition']); ?>
    <?php echo $view['form']->row($form->vars['form']->children['value']); ?>
    <div class="col-xs-2">
        <label class="<?php echo $labelClass; ?>"><?php echo $view['translator']->trans('mautic.report.report.label.removefilter'); ?></label>
        <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-minus-square-o"></i></button>
    </div>
</div>
