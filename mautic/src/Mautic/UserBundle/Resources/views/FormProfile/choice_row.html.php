<?php
$hasErrors = count($form->vars['errors']);
$feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($hasErrors)) ? " has-error" : "";
?>
<div class="row">
    <div class="form-group col-sm-12 col-md-12 col-lg-11<?php echo $feedbackClass; ?>">
        <?php echo $view['form']->label($form, $label) ?>
        <?php echo $view['form']->widget($form) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
</div>