<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>


<div class="offcanvas-container" data-toggle="offcanvas" data-options='{"openerClass":"offcanvas-opener", "closerClass":"offcanvas-closer"}'>
    <!-- START Wrapper -->
    <div class="offcanvas-wrapper">

        <?php echo $view->render('MauticCoreBundle:RightPanel:left.html.php'); ?>
        <?php echo $view->render('MauticCoreBundle:RightPanel:main.html.php'); ?>
        <?php echo $view->render('MauticCoreBundle:RightPanel:right.html.php'); ?>

    </div>
    <!--/ END Wrapper -->
</div>