<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

<div id="dashboard-widgets">
    <?php if ($widgets): ?>
        <?php foreach ($widgets as $widget): ?>
            <div class="widget" data-widget-id="<?php echo $widget->getId(); ?>"  style="width: <?php echo !empty($widget->getWidth()) ? $widget->getWidth() . '' : '100' ?>%; height: <?php echo !empty($widget->getHeight()) ? $widget->getHeight() . 'px' : '300px' ?>">
                <?php echo $view->render('MauticDashboardBundle:Widget:detail.html.php', array(
                    'widget' => $widget
                )); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="clearfix"></div>
</div>
