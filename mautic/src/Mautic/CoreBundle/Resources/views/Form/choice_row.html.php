<?php
//select boxes vs radio/checkboxes
$responsiveClasses = ((!$form->vars['expanded'] && !$form->vars['multiple']) ||
    (!$form->vars['expanded'] && $form->vars['multiple'])) ?
        ' col-xs-12 col-sm-12 col-md-8 col-lg-6' :
        ' col-xs-12 col-sm-12 col-md-12 col-lg-12';
$feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($errors)) ? ' has-error has-feedback' : '';
?>
<div class="row">
    <div class="form-group <?php echo $responsiveClasses.$feedbackClass; ?>">
        <?php echo $view['form']->label($form, $label) ?>
        <?php echo $view['form']->widget($form) ?>
    </div>
</div>