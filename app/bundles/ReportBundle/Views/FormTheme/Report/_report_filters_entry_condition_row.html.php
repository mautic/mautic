<label class="<?php echo $form->vars['label_attr']['class']; ?>" for="<?php echo $form->vars['id']; ?>"><?php echo $view['translator']->trans($form->vars['label']); ?></label>
<select id="<?php echo $form->vars['id']; ?>" name="<?php echo $form->vars['full_name']; ?>" class="<?php echo $form->vars['attr']['class']; ?>" onchange="Mautic.checkReportCondition('<?php echo $form->vars['id']; ?>')">
    <?php foreach ($form->vars['choices'] as $condition) {
    ?>
    <option value="<?php echo $condition->value; ?>"<?php echo ($condition->value == $form->vars['data']) ? ' selected' : '' ?>><?php echo $condition->label; ?></option>
    <?php
} ?>
</select>
