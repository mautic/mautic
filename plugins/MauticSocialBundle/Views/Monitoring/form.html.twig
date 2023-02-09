<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'monitoring');

echo $view['assets']->includeScript('plugins/MauticSocialBundle/Assets/js/social.js');
?>

<?php
    $header = ($entity->getId()) ?
    $view['translator']->trans('mautic.social.monitoring.menu.edit',
        ['%name%' => $view['translator']->trans($entity->getTitle())]) :
        $view['translator']->trans('mautic.social.monitoring.menu.new');

        $view['slots']->set('headerTitle', $header);
?>

<?php echo $view['form']->start($form); ?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">
        <div class="pa-md">
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo $view['form']->row($form['title']); ?>
                        </div>
                        <div class="col-md-6">
                            <?php echo $view['form']->row($form['networkType']); ?>
                        </div>
                    </div>
                    <div id="properties-container">
                        <div class="row">
                        <?php if (isset($form['properties'])): ?>
                        <?php foreach ($form['properties'] as $child):?>
                            <div class="col-md-6">
                                <?php echo $view['form']->row($child); ?>
                            </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </div>
                    </div>
                    <?php echo $view['form']->row($form['description']); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['isPublished']); ?>
            <?php echo $view['form']->row($form['publishUp']); ?>
            <?php echo $view['form']->row($form['publishDown']); ?>
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['lists']); ?>
        </div>
    </div>
</div>

<?php echo $view['form']->end($form); ?>

<?php
$view['slots']->append('modal', $this->render('MauticCoreBundle:Helper:modal.html.php', [
            'id'            => 'formComponentModal',
            'header'        => false,
            'footerButtons' => true,
        ]));
?>