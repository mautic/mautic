<?php
$list            = $form->children['list'];
$parentHasErrors = $view['form']->containsErrors($form->parent);
$hasErrors       = count($list->vars['errors']);

if ($parentHasErrors && $hasErrors && empty($list->vars['value']) && isset($form->parent->children['properties']['list'])) {
    // Work around for Symfony bug not repopulating values
    $list = $form->parent->children['properties']['list'];
}

$feedbackClass = (!empty($hasErrors)) ? ' has-error' : '';
$datePrototype = (isset($list->vars['prototype'])) ?
    $view->escape('<div class="sortable">'.$view['form']->widget($list->vars['prototype']).'</div>') : '';

?>
<div class="row">
    <div data-toggle="sortablelist" data-prefix="<?php echo $form->vars['id']; ?>" class="form-group col-xs-12 <?php echo $feedbackClass; ?>" id="<?php echo $form->vars['id']; ?>_list" style="overflow:auto">
        <?php echo $view['form']->label($form, $label) ?>
        <a  data-prototype="<?php echo $datePrototype; ?>"
           class="btn btn-warning btn-xs btn-add-item" href="#" id="<?php echo $form->vars['id']; ?>_additem">
            <?php echo $view['translator']->trans($addValueButton); ?>
        </a>
        <?php echo $view['form']->block($list, 'sortablelist_errors'); ?>
        <?php if ($isSortable): ?>
        <div id="sortable-<?php echo $form->vars['id']; ?>" class="list-sortable" <?php foreach ($attr as $k => $v) {
    printf('%s="%s" ', $view->escape($k), $view->escape($v));
}?>>
        <?php endif; ?>
            <?php foreach ($list->children as $key => $item): ?>
            <?php echo $view['form']->block($item, 'sortablelist_entry_row'); ?>
            <?php endforeach; ?>
        </div>
        <?php if ($isSortable): ?>
        <input type="hidden" class="sortable-itemcount" id="<?php echo $form->vars['id']; ?>_itemcount" value="<?php echo count($list); ?>" />
        <?php endif; ?>
    </div>
</div>