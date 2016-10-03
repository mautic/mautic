<?php
$preaddonAttr  = (isset($attr['preaddon_attr'])) ? $attr['preaddon_attr'] : [];
$postaddonAttr = (isset($attr['postaddon_attr'])) ? $attr['postaddon_attr'] : [];

if (!empty($attr['preaddon']) || !empty($attr['postaddon']) || !empty($attr['preaddon_text']) || !empty($attr['postaddon_text'])): ?>
    <div class="input-group">
        <?php if (!empty($attr['preaddon'])): ?>
            <span class="input-group-addon preaddon" <?php foreach ($preaddonAttr as $k => $v) {
    printf('%s="%s" ', $view->escape($k), $view->escape($v));
}?>>
        <i class="<?php echo $attr['preaddon']; ?>"></i>
    </span>
        <?php endif; ?>
        <?php if (!empty($attr['preaddon_text'])): ?>
            <span class="input-group-addon preaddon" <?php foreach ($preaddonAttr as $k => $v) {
    printf('%s="%s" ', $view->escape($k), $view->escape($v));
}?>>
        <span><?php echo $attr['preaddon_text']; ?></span>
    </span>
        <?php endif; ?>
        <input autocomplete="false" type="<?php echo isset($type) ? $view->escape($type) : 'text' ?>"
            <?php echo $view['form']->block($form, 'widget_attributes') ?><?php if (!empty($value) || is_numeric($value)): ?>
            value="<?php echo $view->escape($value) ?>"<?php endif ?> />

        <?php if (!empty($attr['postaddon'])): ?>
            <span class="input-group-addon postaddon" <?php foreach ($postaddonAttr as $k => $v) {
    printf('%s="%s" ', $view->escape($k), $view->escape($v));
}?>>
        <i class="<?php echo $attr['postaddon']; ?>"></i>
    </span>
        <?php endif; ?>
        <?php if (!empty($attr['postaddon_text'])): ?>
            <span class="input-group-addon postaddon" <?php foreach ($postaddonAttr as $k => $v) {
    printf('%s="%s" ', $view->escape($k), $view->escape($v));
}?>>
        <span><?php echo $attr['postaddon_text']; ?></span>
    </span>
        <?php endif; ?>
    </div>
<?php else: ?>
    <input type="<?php echo isset($type) ? $view->escape($type) : 'text' ?>"
        <?php echo $view['form']->block($form, 'widget_attributes') ?><?php if (!empty($value) || is_numeric($value)): ?>
        value="<?php echo $view->escape($value) ?>"<?php endif ?> />
<?php endif; ?>
