id="<?php echo $view->escape($id) ?>" name="<?php echo $view->escape($full_name) ?>" <?php if ($disabled): ?>disabled="disabled" <?php endif ?>
<?php foreach ($attr as $k => $v): ?>
<?php if (in_array($k, ['icon'])) {
    continue;
} ?>
<?php if (in_array($v, ['placeholder', 'title'], true)): ?>
<?php printf('%s="%s" ', $view->escape($k), $view->escape($view['translator']->trans($v, [], $translation_domain))) ?>
<?php elseif ($v === true): ?>
<?php printf('%s="%s" ', $view->escape($k), $view->escape($k)) ?>
<?php elseif ($v !== false): ?>
<?php printf('%s="%s" ', $view->escape($k), $view->escape($v)) ?>
<?php endif ?>
<?php endforeach ?>
