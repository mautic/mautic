<?php
$hasErrors = count($form->vars['errors']);
$feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($hasErrors)) ? " has-error" : "";
?>
<div class="row">
    <div class="form-group col-xs-12<?php echo $feedbackClass; ?>">
        <?php echo $view['form']->label($form, $label) ?>
        <?php if (!empty($form->vars['attr']['tooltip'])): ?>
            <span data-toggle="tooltip" data-container="body" data-placement="top"
                  data-original-title="<?php echo $view['translator']->trans($form->vars['attr']['tooltip']); ?>">
            <i class="fa fa-question-circle"></i>
        </span>
        <?php endif; ?>

        <?php if (isset($prototype)): ?>
        <?php $attr['data-prototype'] = $view->escape($view['form']->row($prototype)) ?>
        <?php endif ?>
        <?php echo $view['form']->widget($form, array('attr' => $attr)) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
</div>