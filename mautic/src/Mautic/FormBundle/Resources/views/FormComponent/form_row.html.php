<?php
$hasErrors = count($form->vars['errors']);
$feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($hasErrors)) ? " has-error" : "";
?>
<div class="row">
    <div class="form-group col-sm-12<?php echo $feedbackClass; ?>">
        <?php echo $view['form']->label($form, $label) ?>
        <?php if (isset($prototype)): ?>
        <?php $attr['data-prototype'] = $view->escape($view['form']->row($prototype)) ?>
        <?php endif ?>
        <?php echo $view['form']->widget($form, array('attr' => $attr)) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
</div>