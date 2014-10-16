<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="offcanvas-right" id="OffCanvasRight">
    <!-- start: sidebar header -->
    <div class="sidebar-header box-layout" id="OffCanvasRightHeader">
        <div class="col-xs-6 va-m">
            <a href="javascript:void(0);" class="offcanvas-closer"><span class="fa fa-arrow-left fs-16"></span></a>
        </div>
        <div class="col-xs-6 va-m text-right">
            <a href="javascript:void(0);"><span class="fa fa-info fs-16"></span></a>
        </div>
    </div>
    <!--/ end: sidebar header -->

    <!-- start: sidebar footer -->
    <div class="sidebar-footer box-layout">
        <div class="cell va-m">
            <div class="form-control-icon">
                <input id="ChatMessageInput" type="text" class="form-control bg-transparent bdr-rds-0 bdr-w-0" placeholder="<?php echo $view['translator']->trans('mautic.chat.chat.input.placeholder'); ?>">
                <span class="the-icon fa fa-paper-plane text-white dark-sm"></span><!-- must below `form-control` -->
            </div>
        </div>
    </div>
    <!--/ end: sidebar footer -->

    <!-- start: sidebar content -->
    <div class="sidebar-content">
        <!-- scroll-content -->
        <div class="scroll-content slimscroll" id="OffCanvasRightContent">
            <!-- put the chat bubbles here -->
            <ul class="media-list media-list-bubble">

            </ul>
        </div>
        <!--/ scroll-content -->
    </div>
    <!--/ end: sidebar content -->
</div>
<!--/ Offcanvas Right -->