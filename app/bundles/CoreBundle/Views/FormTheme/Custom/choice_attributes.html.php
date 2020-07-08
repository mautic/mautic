<?php foreach ($choice_attr as $k => $v): ?>
<?php if (true === $v): ?>
<?php printf('%s="%s" ', $view->escape($k), $view->escape($k)); ?>
<?php elseif (false !== $v): ?>
<?php printf('%s="%s" ', $view->escape($k), $view->escape($v)); ?>
<?php endif; ?>
<?php endforeach; ?>
