<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set("headerTitle", $view['translator']->trans("mautic.mapper.title.dashboard"));
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
                                <a href="<?php echo $this->container->get('router')->generate('mautic_mapper_client_index', array("application"  => $application['bundle'])); ?>">
                                    <img src="<?php echo $view['assets']->getUrl($application['icon']); ?>" width="100px" />
                                </a>
                                <p><?php echo $application['name']; ?></p>
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