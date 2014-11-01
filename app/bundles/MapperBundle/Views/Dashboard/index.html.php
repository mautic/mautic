<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set("headerTitle", "Integrations");
$view['slots']->set('mauticContent', 'Integrations');
?>
<div class="box-layout">
    <div class="np col-md-12 height-auto bg-white">
        <div class="bg-auto bg-dark-xs">
            <div class="pa-md mb-lg">
                <?php $i = 1; foreach ($applications as $index => $application): ?>
                    <?php if ($i % 4 == 0): ?>
                    </div>
                    <?php $i = 1; endif; ?>
                    <?php if ($i == 1): ?>
                    <div class="row">
                    <?php endif; ?>
                    <div class="col-md-3">
                        <div class="panel mb-0">
                            <div class="text-center doughnut-wrapper">
                                <a href="<?php echo $application->getAppLink(); ?>">
                                    <img src="<?php echo $view['assets']->getUrl($application->getImage()); ?>">
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php if ($index == count($application) - 1): ?>
                    </div>
                    <?php endif; ?>
                <?php $i++; endforeach; ?>
            </div>
        </div>
    </div>
</div>