<?php
$preaddonAttr  = (isset($form->vars['attr']['preaddon_attr'])) ? $form->vars['attr']['preaddon_attr'] : array();
$postaddonAttr = (isset($form->vars['attr']['postaddon_attr'])) ? $form->vars['attr']['postaddon_attr'] : array();

if (!empty($form->vars['attr']['tooltip']) ||
    !empty($form->vars['attr']['preaddon']) ||
    !empty($form->vars['attr']['postaddon'])): ?>
<div class="input-group">
    <?php if (!empty($form->vars['attr']['preaddon'])): ?>
        <span class="input-group-addon preaddon" <?php foreach ($preaddonAttr as $k => $v) { printf('%s="%s" ', $view->escape($k), $view->escape($v)); }?>>
        <i class="<?php echo $form->vars['attr']['preaddon']; ?>"></i>
    </span>
    <?php endif; ?>

    <input autocomplete="off" type="<?php echo isset($type) ? $view->escape($type) : 'text' ?>"
        <?php echo $view['form']->block($form, 'widget_attributes') ?><?php if (!empty($value) || is_numeric($value)): ?>
        value="<?php echo $view->escape($value) ?>"<?php endif ?> />

    <?php if (!empty($form->vars['attr']['tooltip'])): ?>
    <span class="input-group-addon" data-toggle="tooltip" data-container="body" data-placement="top"
          data-original-title="<?php echo $view['translator']->trans($form->vars['attr']['tooltip']); ?>">
        <i class="fa fa-question-circle"></i>
    </span>

    <?php elseif (!empty($form->vars['attr']['postaddon'])): ?>
    <span class="input-group-addon postaddon" <?php foreach ($postaddonAttr as $k => $v) { printf('%s="%s" ', $view->escape($k), $view->escape($v)); }?>>
        <i class="<?php echo $form->vars['attr']['postaddon']; ?>"></i>
    </span>
    <?php endif; ?>
</div>
<?php else: ?>
<input type="<?php echo isset($type) ? $view->escape($type) : 'text' ?>"
    <?php echo $view['form']->block($form, 'widget_attributes') ?><?php if (!empty($value) || is_numeric($value)): ?>
    value="<?php echo $view->escape($value) ?>"<?php endif ?> />
<?php endif; ?>


