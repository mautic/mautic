<?php
//if currentPassword exists, the element is on the profile user form
$responsiveClasses = (isset($form->parent->children['currentPassword']) ||
    (isset($form->parent->parent) && isset($form->parent->parent->children['currentPassword']))) ?
    ' col-sm-12 col-md-12 col-lg-11' : ' col-sm-12 col-md-8 col-lg-6';
$feedbackClass     = ($app->getRequest()->getMethod() == 'POST' && !empty($errors)) ? " has-error has-feedback" : "";
?>
<div class="row">
    <div class="form-group<?php echo $responsiveClasses. $feedbackClass; ?>">
        <?php echo $view['form']->label($form, $label) ?>
        <?php echo $view['form']->widget($form) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
</div>