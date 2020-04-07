<div class="choice-wrapper col-xs-4">
    <label class="<?php echo $form->vars['label_attr']['class']; ?>" for="<?php echo $form->vars['id']; ?>"><?php echo $view['translator']->trans($form->vars['label']); ?></label>
    <select id="<?php echo $form->vars['id']; ?>" name="<?php echo $form->vars['full_name']; ?>" class="<?php echo $form->vars['attr']['class']; ?>">
        <?php foreach ($form->vars['choices'] as $direction) : ?>
        <option value="<?php echo $view->escape($direction->value); ?>"<?php echo ($direction->value == $form->vars['data']) ? ' selected' : ''; ?>>
            <?php echo $direction->label; ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>
