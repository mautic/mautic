<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:FormTheme:form_simple.html.php');
$view->addGlobal('translationBase', 'mautic.sms');
$view->addGlobal('mauticContent', 'sms');

?>

<?php $view['slots']->start('primaryFormContent'); ?>
<div class="row">
    <div class="col-md-6">
        <?php echo $view['form']->row($form['name']); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <?php echo $view['form']->row($form['message']); ?>
    </div>
</div>

<div class="row">
    <?php foreach ($form->children['properties'] as $name => $f): ?>
        <?php if ($row = $view['form']->row($f)): ?>
            <div class="col-xs-6">
                <?php echo $row; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php $view['slots']->stop(); ?>

<?php $view['slots']->start('rightFormContent'); ?>
<?php echo $view['form']->row($form['category']); ?>
<?php echo $view['form']->row($form['language']); ?>
<div class="hide">
    <?php echo $view['form']->row($form['isPublished']); ?>
    <?php echo $view['form']->row($form['publishUp']); ?>
    <?php echo $view['form']->row($form['publishDown']); ?>

    <?php echo $view['form']->rest($form); ?>
</div>
<?php $view['slots']->stop(); ?>
