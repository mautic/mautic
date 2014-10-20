<?php
$hasErrors = count($form->vars['errors']);
$feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($hasErrors)) ? " has-error" : "";
if (isset($view['form']->vars['attr']['row-width'])) {
    $colClasses = $view['form']->vars['attr']['row-width'];
    unset($view['form']->vars['attr']['row-width']);
} else {
    $colClasses = 'col-xs-12 col-sm-8 col-md-6';
}
?>
<div class="row">
    <div class="form-group <?php echo $colClasses . $feedbackClass; ?>">
        <?php echo $view['form']->label($form, $label) ?>
        <?php if (!empty($form->vars['attr']['tooltip'])): ?>
            <span data-toggle="tooltip" data-container="body" data-placement="top"
                  data-original-title="<?php echo $view['translator']->trans($form->vars['attr']['tooltip']); ?>">
            <i class="fa fa-question-circle"></i>
        </span>
        <?php endif; ?>
        <?php echo $view['form']->widget($form) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
</div>