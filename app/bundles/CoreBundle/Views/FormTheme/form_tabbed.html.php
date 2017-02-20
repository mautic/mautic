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
$tabs           = $view['slots']->get('formTabs', []);
$aboveTabs      = $view['slots']->get('aboveTabsContent', '');
$belowTabs      = $view['slots']->get('belowTabsContent', '');
$hasRightColumn = $view['slots']->has('rightFormContent');
$modalView      = $app->getRequest()->get('modal', false) || $view['slots']->get('inModal', false);

?>
<?php $view['slots']->start('mainFormContent'); ?>
<?php echo $view['form']->start($form); ?>
    <div class="box-layout">
        <div class="col-md-<?php echo $hasRightColumn ? 9 : 12; ?> height-auto bg-white">
            <div class="row">
                <div class="col-xs-12">
                    <?php echo $view['form']->errors($form); ?>
                    <?php echo $aboveTabs; ?>
                    <?php echo $view['content']->getCustomContent('tabs.above', $mauticTemplateVars); ?>
                    <?php echo $view->render('MauticCoreBundle:Helper:tabs.html.php', ['tabs' => $tabs]); ?>
                    <div class="pr-md pl-md">
                        <?php if ($view['slots']->has('_content')): ?>
                        <?php $view['slots']->output('_content'); ?>
                        <?php else: ?>
                        <?php echo $view['form']->rest($form); ?>
                        <?php endif; ?>
                    </div>
                    <?php echo $belowTabs; ?>
                    <?php echo $view['content']->getCustomContent('tabs.below', $mauticTemplateVars); ?>
                </div>
            </div>
        </div>
        <?php if ($hasRightColumn): ?>
        <div class="col-md-3 bg-white height-auto bdr-l">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php $view['slots']->output('rightFormContent'); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php echo $view['form']->end($form); ?>
<?php $view['slots']->stop(); ?>
