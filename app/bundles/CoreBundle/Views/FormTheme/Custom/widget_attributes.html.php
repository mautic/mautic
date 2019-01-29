id="<?php echo $view->escape($id) ?>" name="<?php echo $view->escape($full_name) ?>" <?php if ($read_only): ?>readonly="readonly" <?php endif ?>
<?php if ($disabled): ?>disabled="disabled" <?php endif ?>
<?php if ($required): ?>required="required" <?php endif ?>
<?php if ($max_length): ?>maxlength="<?php echo $view->escape($max_length) ?>" <?php endif ?>
<?php if ($pattern): ?>pattern="<?php echo $view->escape($pattern) ?>" <?php endif ?>
<?php

use Mautic\FormBundle\Helper\FormFieldHelper;

foreach ($attr as $k => $v) {
    if (in_array($k, ['tooltip', 'preaddon', 'preaddon_attr', 'postaddon_attr'])) {
        continue;
    }
    if (in_array($k, ['placeholder', 'title'], true)) {
        printf('%s="%s" ', $view->escape($k), $view->escape($view['translator']->trans($v, [], $translation_domain)));
    } elseif ($v === true) {
        printf('%s="%s" ', $view->escape($k), $view->escape($k));
    } elseif (is_array($v)) {
        $v = FormFieldHelper::formatList(FormFieldHelper::FORMAT_BAR, $v);
        printf('%s="%s" ', $view->escape($k), $view->escape($v));
    } elseif ($v !== false) {
        printf('%s="%s" ', $view->escape($k), $view->escape($v));
    }
}

// Disable by default and use false for chrome support
if (!isset($attr['autocomplete'])) {
    printf('autocomplete="false" ');
}
