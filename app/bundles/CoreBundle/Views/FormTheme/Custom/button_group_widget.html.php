<?php
//apply attributes to radios
$attr = $form->vars['attr'];
?>
<div class="btn-group <?php echo $buttonBlockClass; ?>" data-toggle="buttons">
    <?php foreach ($form as $child): ?>
        <?php $class =
            (!empty($child->vars['checked']) ? ' active' : '').
            (!empty($child->vars['disabled']) || !empty($attr['readonly']) ? ' disabled' : ''); ?>
        <label class="btn btn-default<?php echo $class; ?>">
            <?php echo $view['form']->widget($child, ['attr' => $attr]); ?>
            <?php echo $view['translator']->trans($child->vars['label']); ?>
        </label>
    <?php endforeach; ?>
</div>