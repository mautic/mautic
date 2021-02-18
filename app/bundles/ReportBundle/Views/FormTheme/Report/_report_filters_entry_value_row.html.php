<?php $condition = $form->parent->children['condition']->vars['value']; ?>
<div class="form-group mb-0">
    <label class="<?php echo $form->vars['label_attr']['class']; ?>" for="<?php echo $form->vars['id']; ?>"><?php echo $view['translator']->trans($form->vars['label']); ?></label>
    <input type="text" id="<?php echo $form->vars['id']; ?>" name="<?php echo $form->vars['full_name']; ?>" class="<?php echo $form->vars['attr']['class']; ?>" value="<?php echo $view->escape($form->vars['data']); ?>"<?php echo (in_array($condition, ['empty', 'notEmpty'])) ? ' disabled' : ''; ?> />
</div>
