<div <?php echo $view['form']->block($form, 'widget_container_attributes') ?> class="btn-group btn-block" data-toggle="buttons">
    <?php foreach ($form as $child): ?>
        <label class="btn btn-success<?php if (!empty($child->vars['checked'])) echo ' active'; ?>">
            <?php if (strpos($child->vars['cache_key'], '_role_permissions') !== false):?>
            <?php echo $view['form']->widget($child, array(
                'id' => $child->parent->vars['id'] . "_" . $child->vars['value']
            )); ?>
            <?php else: ?>
            <?php echo $view['form']->widget($child); ?>
            <?php endif; ?>
            <?php echo $view['translator']->trans($child->vars['label']); ?>
        </label>
    <?php endforeach; ?>
</div>