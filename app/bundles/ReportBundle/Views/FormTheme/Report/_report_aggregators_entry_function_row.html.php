<div class="choice-wrapper col-xs-4">
<label class="<?php echo $form->vars['label_attr']['class']; ?>" for="<?php echo $form->vars['id']; ?>"><?php echo $view['translator']->trans($form->vars['label']); ?></label>
<select id="<?php echo $form->vars['id']; ?>" name="<?php echo $form->vars['full_name']; ?>" class="<?php echo $form->vars['attr']['class']; ?>" onchange="Mautic.checkReportCondition('<?php echo $form->vars['id']; ?>')">
    <?php foreach ($form->vars['choices'] as $function) {
    ?>
        <option value="<?php echo $function->value; ?>"<?php echo ($function->value == $form->vars['data']) ? ' selected' : '' ?>><?php echo $function->label; ?></option>
        <?php

} ?>
</select>
</div>