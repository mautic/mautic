<<?php echo $form->vars['attr']['tag']; ?>
    class="<?php echo $form->vars['attr']['class']; ?>"
    <?php if (!empty($form->vars['id'])) echo ' id="' . $form->vars['id'] . '"'; ?>>
    <span><?php echo $view['translator']->trans($form->vars['attr']['text']); ?></span>
</<?php echo $form->vars['attr']['tag']; ?>>