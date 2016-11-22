<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') :
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'asset');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.asset.remote.file.browse'));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <div class="page-list">
<?php endif; ?>
        <?php if (count($integrations)): ?>
            <!-- start: box layout -->
            <div class="box-layout">
                       <!-- step container -->
                <div class="col-md-3 bg-white">
                    <div class="pt-md pr-md pb-md">
                        <ul class="list-group list-group-tabs">
                            <?php $step = 1; ?>
                            <?php /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integration */ ?>
                            <?php foreach ($integrations as $integration): ?>
                                <li class="list-group-item<?php if ($step === 1) {
    echo ' active';
} ?>" id="tab<?php echo $integration->getName(); ?>">
                                    <a href="#" class="steps" onclick="Mautic.updateRemoteBrowser('<?php echo $integration->getName(); ?>');">
                                        <?php echo $integration->getDisplayName(); ?>
                                    </a>
                                </li>
                                <?php ++$step; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <!--/ step container -->

                <!-- container -->
                <div class="col-md-9 bg-auto bdr-l">
                    <div id="remoteFileBrowser">
                        <div class="alert alert-warning col-md-6 col-md-offset-3 mt-md">
                            <p><?php echo $view['translator']->trans('mautic.asset.remote.select_service'); ?></p>
                        </div>
                    </div>
                </div>
                <!--/ end: container -->
            </div>
            <!--/ end: box layout -->
        <?php endif; ?>
<?php if ($tmpl == 'index') : ?>
    </div>
</div>
<?php endif; ?>
