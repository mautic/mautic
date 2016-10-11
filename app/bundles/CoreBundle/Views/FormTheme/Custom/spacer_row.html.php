<?php $class = (!empty($class)) ? ' class="'.$class.'"' : ''; ?>
<<?php echo $tag ?><?php echo $class; ?><?php if (!empty($form->vars['id'])) {
    echo ' id="'.$form->vars['id'].'"';
} ?>>
    <span><?php echo $view['translator']->trans($text); ?></span>
</<?php echo $tag ?>>