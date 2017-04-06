<?php
$labelClass = (empty($form->vars['label_attr']['class'])) ? 'control-label' : $form->vars['label_attr']['class'];
?>
<div id="<?php echo $form->vars['id']; ?>_container" class="row mb-sm">
    <div class="choice-wrapper col-md-4 float-left"><?php echo $view['form']->row($form->vars['form']->children['column']); ?></div>
    <div class="choice-wrapper col-md-2 float-left"><?php echo $view['form']->row($form->vars['form']->children['function']); ?></div>
    <div class="col-xs-2 mt-lg">
        <button type="button" class="btn btn-danger" onclick="Mautic.removeReportRow('<?php echo $form->vars['id']; ?>_container');" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.report.report.label.removefilter'); ?>"><i class="fa fa-minus-square-o"></i></button>
    </div>
</div>