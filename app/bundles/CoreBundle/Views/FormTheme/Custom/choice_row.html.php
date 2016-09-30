<?php
$hasErrors     = count($form->vars['errors']);
$feedbackClass = (!empty($hasErrors)) ? ' has-error' : '';

//apply attributes to radios
$attr = $form->vars['attr'];
?>
<div class="row">
    <div class="form-group col-xs-12 <?php echo $feedbackClass; ?>">
        <?php echo $view['form']->label($form, $label) ?>
        <div class="choice-wrapper">
            <?php if ($expanded && $multiple): ?>
            <?php foreach ($form->children as $child): ?>
                <div class="checkbox">
                    <label>
                        <?php echo $view['form']->widget($child, ['attr' => $attr]); ?>
                        <?php echo $view['translator']->trans($child->vars['label']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
            <?php echo $view['form']->widget($form); ?>
            <?php endif; ?>
            <?php echo $view['form']->errors($form); ?>
        </div>
    </div>
</div>
