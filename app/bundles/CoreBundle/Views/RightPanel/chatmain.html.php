<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="offcanvas-main" id="OffCanvasMain">

    <!-- start: sidebar header -->
    <div class="sidebar-header box-layout" id="OffCanvasMainHeader">
        <div class="va-m text-center mt15">
            <a href="javascript:void(0);" class="btn btn-primary offcanvas-opener offcanvas-open-ltr"><i class="fa fa-fw fa-wrench"></i><?php echo $view['translator']->trans('mautic.core.settings'); ?></a>
        </div>
    </div>
    <!--/ end: sidebar header -->
    <!-- start: sidebar content -->
    <div class="sidebar-content">
        <!-- scroll-content -->
        <div class="scroll-content slimscroll" id="OffCanvasMainContent">
            <?php echo $view['actions']->render(new \Symfony\Component\HttpKernel\Controller\ControllerReference('MauticChatBundle:Default:index')); ?>
        </div>
        <!--/ scroll-content -->
    </div>
    <!--/ end: sidebar content -->
</div>
<!--/ Offcanvas Content -->
