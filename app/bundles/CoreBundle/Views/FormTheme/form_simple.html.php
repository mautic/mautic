<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:FormTheme:form.html.php');
$hasRightColumn = $view['slots']->has('rightFormContent');
$modalView      = $app->getRequest()->get('modal', false) || $view['slots']->get('inModal', false);
?>

<?php $view['slots']->start('mainFormContent'); ?>
<div class="box-layout">
    <div class="col-md-<?php echo $hasRightColumn && !$modalView ? 9 : 12; ?> bg-auto height-auto bdr-r">
        <div class="pa-md">
            <?php $view['slots']->output('primaryFormContent'); ?>
        </div>
    </div>
    <?php if ($hasRightColumn): ?>
    <div class="col-md-<?php echo $modalView ? 12 : 3; ?> bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php $view['slots']->output('rightFormContent'); ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $view['slots']->stop();
