<div <?php echo $view['form']->block($form, 'widget_container_attributes') ?> class="btn-group btn-block" data-toggle="buttons">
    <?php foreach ($form as $child): ?>
        <?php $class =
            (!empty($child->vars['checked']) ? ' active' : '') .
            (!empty($child->vars['disabled']) || !empty($child->vars['read_only']) ? ' disabled' : ''); ?>
        <label class="btn btn-success<?php echo $class; ?>">
            <?php echo $view['form']->widget($child); ?>
            <?php echo $view['translator']->trans($child->vars['label']); ?>
        </label>
    <?php endforeach; ?>
</div>