<?php
$hasErrors = count($form->vars['errors']);
$feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($hasErrors)) ? " has-error" : "";
?>
<div id="tableOrderContainer" class="row">
    <div class="form-group col-md-12<?php echo $feedbackClass; ?>">
        <?php if (!empty($form->vars['attr']['tooltip'])): ?>
            <span data-toggle="tooltip" data-container="body" data-placement="top" data-original-title="<?php echo $view['translator']->trans($form->vars['attr']['tooltip']); ?>">
            <i class="fa fa-question-circle"></i>
        </span>
        <?php endif; ?>
        <?php echo $view['form']->widget($form) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
    <div class="col-xs-12">
        <button type="button" class="btn btn-primary" onclick="Mautic.addReportRow('report_tableOrder');"><?php echo $view['translator']->trans('mautic.report.report.label.addtableorder'); ?></button>
    </div>
</div>
