id="<?php echo $view->escape($id); ?>" name="<?php echo $view->escape($full_name); ?>"
<?php if ($disabled): ?>disabled="disabled" <?php endif; ?>
<?php if ($required): ?>required="required" <?php endif; ?>
<?php

use Mautic\FormBundle\Helper\FormFieldHelper;

foreach ($attr as $k => $v) {
    if (in_array($k, ['tooltip', 'preaddon', 'preaddon_attr', 'postaddon_attr'])) {
        continue;
    }
    if (in_array($k, ['placeholder', 'title'], true)) {
        printf('%s="%s" ', $view->escape($k), $view->escape($view['translator']->trans($v, [], $translation_domain)));
    } elseif (true === $v) {
        printf('%s="%s" ', $view->escape($k), $view->escape($k));
    } elseif (is_array($v)) {
        $v = FormFieldHelper::formatList(FormFieldHelper::FORMAT_BAR, $v);
        printf('%s="%s" ', $view->escape($k), $view->escape($v));
    } elseif (false !== $v) {
        printf('%s="%s" ', $view->escape($k), $view->escape($v));
    }
}

// Disable by default and use false for chrome support
if (!isset($attr['autocomplete'])) {
    printf('autocomplete="false" ');
}
