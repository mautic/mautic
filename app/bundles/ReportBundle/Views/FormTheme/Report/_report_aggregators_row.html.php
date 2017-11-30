<?php
$hasErrors     = count($form->vars['errors']);
$feedbackClass = (!empty($hasErrors)) ? ' has-error' : '';
?>
<div id="aggregatorsContainer" class="row">
    <div class="form-group col-md-12<?php echo $feedbackClass; ?>">
        <?php echo $view['form']->widget($form) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
    <div class="col-xs-12">
        <button id="aggregators-button" disabled type="button" class="btn btn-primary" onclick="Mautic.addReportRow('report_aggregators');"><?php echo $view['translator']->trans('mautic.report.report.label.function'); ?></button>
    </div>
</div>