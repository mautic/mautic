<?php if (!$label) {
    $label = $view['form']->humanize($name);
} ?>
<button type="<?php echo isset($type) ? $view->escape($type) : 'button'; ?>"
    <?php echo $view['form']->block($form, 'button_attributes'); ?>>
    <?php if (!empty($form->vars['attr']['icon'])): ?>
    <i class="<?php echo $form->vars['attr']['icon']; ?> "></i>
    <?php endif; ?>
    <?php echo $view->escape($view['translator']->trans($label, [], $translation_domain)); ?>
</button>
