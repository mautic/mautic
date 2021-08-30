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

$view['slots']->set('mauticContent', 'tweet');
echo $view['assets']->includeScript('plugins/MauticSocialBundle/Assets/js/social.js', 'composeSocialWatcher', 'composeSocialWatcher');
?>

<?php
    $header = ($entity->getId()) ?
    $view['translator']->trans('mautic.social.tweet.menu.edit',
        ['%name%' => $view['translator']->trans($entity->getName())]) :
        $view['translator']->trans('mautic.social.tweet.menu.new');

        $view['slots']->set('headerTitle', $header);
?>

<?php echo $view['form']->start($form); ?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">
        <div class="pa-md">
            <div class="row">
                <div class="col-md-4">
                    <?php echo $view['form']->row($form['name']); ?>
                    <?php echo $view['form']->row($form['description']); ?>
                </div>

                <div class="col-md-8">
                    <?php echo $view['form']->row($form['text']); ?>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <label class="control-label">
                                <?php echo $view['translator']->trans('mautic.social.twitter.tweet.handle'); ?>
                            </label>
                            <?php echo $view['form']->row($form['handle']); ?>
                        </div>
                        <div class="col-md-3">
                            <?php echo $view['form']->row($form['asset']); ?>
                        </div>
                        <div class="col-md-3">
                            <?php echo $view['form']->row($form['page']); ?>
                        </div>
                        <div class="col-md-3">
                            <div id="character-count" class="text-right small">
                                <?php echo $view['translator']->trans('mautic.social.twitter.tweet.count'); ?>
                                <span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['category']); ?>
        </div>
    </div>
</div>

<?php echo $view['form']->end($form); ?>
