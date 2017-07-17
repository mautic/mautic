<?php
$list          = $form->children;
$hasErrors     = count($list->vars['errors']);
$feedbackClass = (!empty($hasErrors)) ? ' has-error' : '';
$datePrototype = (isset($list->vars['prototype'])) ?
    $view->escape('<div class="sortable">'.$view['form']->widget($list->vars['prototype']).'</div>') : '';
$feedbackClass = (!empty($hasErrors)) ? ' has-error' : '';
?>
<div class="row">
    <div data-toggle="sortablelist" data-prefix="<?php echo $form->vars['id']; ?>" class="form-group col-xs-12 <?php echo $feedbackClass; ?>" id="<?php echo $form->vars['id']; ?>_list">
        <?php echo $view['form']->label($form, $label) ?>
        <a  data-prototype="<?php echo $datePrototype; ?>"
           class="btn btn-warning btn-xs btn-add-item" href="#" id="<?php echo $form->vars['id']; ?>_additem">
            <?php echo $view['translator']->trans('mautic.core.form.list.additem'); ?>
        </a>
        <?php if ($isSortable): ?>
        <div class="list-sortable">
        <?php endif; ?>
            <?php foreach ($list->children as $item): ?>
            <?php echo $view['form']->block($item, 'sortablelist_entry_row'); ?>
            <?php endforeach; ?>
        </div>
        <?php echo $view['form']->errors($list); ?>
        <?php if ($isSortable): ?>
        <input type="hidden" class="sortable-itemcount" id="<?php echo $form->vars['id']; ?>_itemcount" value="<?php echo count($list); ?>" />
        <?php endif; ?>
    </div>
</div>