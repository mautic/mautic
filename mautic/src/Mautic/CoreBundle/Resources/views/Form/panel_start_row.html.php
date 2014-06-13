    <?php
$attr = $form->vars['attr'];
if (isset($attr['class']))
    $attr['class'] .= ' panel';
else
    $attr['class'] = 'panel';

$headerAttr = $form->vars['headerAttr'];
if (isset($headerAttr['class']))
    $headerAttr['class'] .= ' panel-heading';
else
    $headerAttr['class'] = 'panel-heading';

$bodyAttr = $form->vars['bodyAttr'];
if (isset($bodyAttr['class']))
    $bodyAttr['class'] .= ' panel-collapse collapse';
else
    $bodyAttr['class'] = 'panel-collapse collapse';
$bodyAttr['id'] = $form->vars['bodyId'];
?>
<div <?php foreach ($attr as $k => $v) { printf('%s="%s" ', $view->escape($k), $view->escape($v)); } ?>>
    <div <?php foreach ($headerAttr as $k => $v) { printf('%s="%s" ', $view->escape($k), $view->escape($v)); } ?>>
        <h4 class="panel-title">
            <a data-toggle="collapse" data-parent="<?php echo $form->vars['dataParent']; ?>"
               href="#<?php echo $form->vars['bodyId']; ?>">
                <?php echo $view['translator']->trans($form->vars['label']); ?>
            </a>
        </h4>
    </div>
    <div <?php foreach ($bodyAttr as $k => $v) { printf('%s="%s" ', $view->escape($k), $view->escape($v)); } ?>>
        <div class="panel-body">