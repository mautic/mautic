<?php if (false !== $label): ?>
<?php if ($required) {
    $label_attr['class'] = trim((isset($label_attr['class']) ? $label_attr['class'] : '').' required');
} ?>
<?php if (!$compound) {
    $label_attr['for'] = $id;
} ?>
<?php if (!$label) {
    $label = $view['form']->humanize($name);
} ?>
<?php $tooltip = (!empty($form->vars['attr']['tooltip'])) ? $form->vars['attr']['tooltip'] : false; ?>
<label <?php foreach ($label_attr as $k => $v) {
    printf('%s="%s" ', $view->escape($k), $view->escape($v));
} ?><?php if ($tooltip): ?>data-toggle="tooltip" data-container="body" data-placement="top" title="<?php echo $view['translator']->trans($tooltip); ?>"<?php endif; ?>><?php echo $view->escape($view['translator']->trans($label, [], $translation_domain)) ?><?php if ($tooltip): ?> <i class="fa fa-question-circle"></i><?php endif; ?></label>
<?php endif ?>
