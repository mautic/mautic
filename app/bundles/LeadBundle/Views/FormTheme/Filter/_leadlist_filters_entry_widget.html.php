<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$isPrototype = ('__name__' == $form->vars['name']);
$filterType  = $form['field']->vars['value'];
$inGroup     = isset($form->vars['data']) && 'and' === $form->vars['data']['glue'];
$isBehavior  = isset($fields['behaviors'][$filterType]['label']);
$class       = (isset($form->vars['data']['object']) && 'company' == $form->vars['data']['object']) ? 'fa-building' : 'fa-user';

if ($isBehavior) {
    $object = 'behaviors';
} else {
    $object = (isset($form->vars['data']['object'])) ? $form->vars['data']['object'] : 'lead';
}

if (!$isPrototype && !isset($fields[$object][$filterType]['label'])) {
    return;
}
?>

<div class="panel<?php echo ($inGroup && false === $first) ? ' in-group' : ''; ?>" id="<?php echo $id; ?>">
    <div class="panel-heading <?php echo (!$isPrototype && '0' === $form->vars['name']) ? ' hide' : ''; ?>">
        <div class="panel-glue col-sm-2 pl-0 ">
            <?php echo $view['form']->widget($form['glue']); ?>
        </div>
    </div>
    <div class="panel-body">
        <div class="col-xs-6 col-sm-3 field-name">
            <i class="object-icon fa <?php echo $class; ?>" aria-hidden="true"></i> <span><?php echo ($isPrototype) ? '__label__' : $fields[$object][$filterType]['label']; ?></span>
        </div>

        <div class="col-xs-6 col-sm-3 padding-none">
            <?php echo $view['form']->widget($form['operator']); ?>
        </div>

        <?php $hasErrors = count($form['properties']->vars['errors']); ?>
        <div class="col-xs-10 col-sm-5 padding-none<?php if ($hasErrors): echo ' has-error'; endif; ?>">
            <div class="properties-form">
                <?php echo $view['form']->widget($form['properties']); ?>
            </div>
            <?php echo $view['form']->errors($form['properties']); ?>
        </div>

        <div class="col-xs-2 col-sm-1">
            <a href="javascript: void(0);" class="remove-selected btn btn-default text-danger pull-right"><i class="fa fa-trash-o"></i></a>
        </div>
        <?php echo $view['form']->widget($form['field']); ?>
        <?php echo $view['form']->widget($form['type']); ?>
        <?php echo $view['form']->widget($form['object']); ?>
    </div>
</div>
