<div <?php echo $view['form']->block($form, 'widget_container_attributes') ?> class="btn-group" data-toggle="buttons">
    <?php foreach ($form as $child): ?>
        <label class="btn btn-success<?php if (!empty($child->vars['checked'])) echo ' active'; ?>">
            <?php echo $view['form']->widget($child); ?>
            <?php echo $view['translator']->trans($child->vars['label']); ?>
        </label>
    <?php endforeach; ?>
</div>