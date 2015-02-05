<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') :
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'asset');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.asset.remote.file.browse'));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <div class="page-list">
<?php endif; ?>
        <?php if (count($integrations)): ?>
            <!-- start: box layout -->
            <div class="box-layout">
                       <!-- step container -->
                <div class="col-md-3 bg-white height-auto">
                    <div class="pr-lg pl-lg pt-md pb-md">
                        <ul class="list-group list-group-tabs">
                            <?php $step = 1; ?>
                            <?php /** @var \Mautic\AddonBundle\Integration\AbstractIntegration $integration */ ?>
                            <?php foreach ($integrations as $integration): ?>
                                <li class="list-group-item<?php if ($step === 1) echo " active"; ?>">
                                    <a href="#" class="steps" onclick="Mautic.updateRemoteBrowser('<?php echo $integration->getName(); ?>');">
                                        <?php echo $integration->getDisplayName(); ?>
                                    </a>
                                </li>
                                <?php $step++; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <!--/ step container -->

                <!-- container -->
                <div class="col-md-9 bg-auto height-auto bdr-l">
                    <div id="remoteFileBrowser"></div>
                </div>
                <!--/ end: container -->
            </div>
            <!--/ end: box layout -->
        <?php else: ?>
            <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', array('tip' => 'mautic.asset.noresults.tip')); ?>
        <?php endif; ?>
<?php if ($tmpl == 'index') : ?>
    </div>
</div>
<?php endif; ?>
