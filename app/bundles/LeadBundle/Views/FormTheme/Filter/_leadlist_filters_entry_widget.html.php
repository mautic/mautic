<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$isPrototype = ($form->vars['name'] == '__name__');
$filterType  = $form['field']->vars['value'];
?>

<div class="panel">
    <div class="panel-footer<?php if (!$isPrototype && $form->vars['name'] === '0') echo " hide"; ?>">
        <div class="col-sm-2 pl-0">
            <?php echo $view['form']->widget($form['glue']); ?>
        </div>
    </div>
    <div class="panel-body">
        <div class="col-xs-6 col-sm-3 field-name">
            <span><?php echo ($isPrototype) ? '__label__' : $fields[$filterType]['label']; ?></span>
        </div>

        <div class="col-xs-6 col-sm-3 padding-none">
            <?php echo $view['form']->widget($form['operator']); ?>
        </div>

        <div class="col-xs-10 col-sm-5 padding-none">
            <?php echo $view['form']->widget($form['filter']); ?>
            <?php echo $view['form']->widget($form['display']); ?>
        </div>

        <div class="col-xs-2 col-sm-1">
            <a href="javascript: void(0);" class="remove-selected btn btn-default text-danger pull-right"><i class="fa fa-trash-o"></i></a>
        </div>
        <?php echo $view['form']->widget($form['field']); ?>
        <?php echo $view['form']->widget($form['type']); ?>
    </div>
</div>