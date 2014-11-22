<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="offcanvas-main" id="OffCanvasMain">
    <!-- start: sidebar header -->
    <div class="sidebar-header box-layout"  id="OffCanvasMainHeader">
        <div class="text-center mt-20">
            <h4><?php echo $view['translator']->trans('mautic.core.menu.admin'); ?></h4>
        </div>
    </div>
    <!--/ end: sidebar header -->

    <!-- start: sidebar content -->
    <div class="sidebar-content">
        <!-- scroll-content -->
        <div class="scroll-content slimscroll" id="OffCanvasMainCanvas">
            <!-- start: navigation -->
            <nav class="nav-sidebar">
                <?php echo $view['knp_menu']->render('admin', array("menu" => "admin")); ?>
            </nav>
        </div>
    </div>
    <!--/ end: sidebar content -->
</div>
<!--/ Offcanvas Main -->
