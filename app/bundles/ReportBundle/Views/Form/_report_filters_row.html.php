<?php
$hasErrors = count($form->vars['errors']);
$feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($hasErrors)) ? " has-error" : "";
?>
<div id="filterSelectorContainer" class="row">
    <div class="form-group col-xs-12 col-sm-8 col-md-6<?php echo $feedbackClass; ?>">
        <?php echo $view['form']->label($form, $view['translator']->trans($form->vars['label'])) ?>
        <?php if (!empty($form->vars['attr']['tooltip'])): ?>
            <span data-toggle="tooltip" data-container="body" data-placement="top"
                  data-original-title="<?php echo $view['translator']->trans($form->vars['attr']['tooltip']); ?>">
            <i class="fa fa-question-circle"></i>
        </span>
        <?php endif; ?>
        <?php echo $view['form']->widget($form) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
    <div class="col-xs-12">
        <button class="btn btn-primary"><?php echo $view['translator']->trans('mautic.report.report.label.addfilter'); ?></button>
    </div>
</div>
