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
$view->addGlobal('translationBase', 'mautic.dynamicContent');
$view->addGlobal('mauticContent', 'dynamicContent');
?>

<?php $view['slots']->start('primaryFormContent'); ?>
    <div class="row">
        <div class="col-md-6">
            <?php echo $view['form']->row($form['name']); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <?php echo $view['form']->row($form['content']); ?>
        </div>
    </div>
<?php $view['slots']->stop(); ?>

<?php $view['slots']->start('rightFormContent'); ?>
<?php echo $view['form']->row($form['category']); ?>
<?php echo $view['form']->row($form['language']); ?>
<?php echo $view['form']->row($form['translationParent']); ?>
<div class="hide">
    <div id="publishStatus">
        <?php echo $view['form']->row($form['isPublished']); ?>
        <?php echo $view['form']->row($form['publishUp']); ?>
        <?php echo $view['form']->row($form['publishDown']); ?>
    </div>

    <?php echo $view['form']->rest($form); ?>
</div>
<?php $view['slots']->stop(); ?>