<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="offcanvas-left" id="OffCanvasLeft">
    <!-- start: sidebar header -->
    <div class="sidebar-header box-layout"  id="OffCanvasLeftHeader">
        <div class="col-xs-6 va-m">
            <a href="javascript:void(0);" class="offcanvas-closer"><span class="fa fa-arrow-left fs-16"></span></a>
        </div>
        <div class="col-xs-6 va-m text-right">
            <!-- <a href="javascript:void(0);"><span class="fa fa-info fs-16"></span></a> -->
        </div>
    </div>
    <!--/ end: sidebar header -->

    <!-- start: sidebar content -->
    <div class="sidebar-content">
        <!-- scroll-content -->
        <div class="scroll-content slimscroll" id="OffCanvasLeftCanvas">
            <!-- start: navigation -->
            <nav class="nav-sidebar">
                <?php echo $view['knp_menu']->render('admin', array("menu" => "admin")); ?>
            </nav>
        </div>
    </div>
    <!--/ end: sidebar content -->
</div>
<!--/ Offcanvas Left -->
