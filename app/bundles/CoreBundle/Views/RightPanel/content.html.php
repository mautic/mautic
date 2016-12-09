<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//for main
if ($canvas == 'Main') {
    if (!$hasLeft && !$hasRight) {
        $class = 'col-xs-12';
    } elseif ($hasLeft && $hasRight) {
        $class = 'col-xs-10';
    } else {
        $class = 'col-xs-11';
    }
}
?>

<!-- start: sidebar header -->
<div class="sidebar-header box-layout"  id="OffCanvas<?php echo $canvas; ?>Header">
    <?php if ($canvas == 'Left'): ?>
    <div class="col-xs-11 pt-lg text-center">
        <h4><?php echo $view['translator']->trans($canvasContent['header']); ?></h4>
    </div>
    <div class="col-xs-1 pt-lg text-right">
        <a href="javascript:void(0);" class="offcanvas-closer"><span class="fa fa-arrow-right fs-16"></span></a>
    </div>

    <?php elseif ($canvas == 'Right'): ?>
    <div class="col-xs-1 pt-lg text-left">
        <a href="javascript:void(0);" class="offcanvas-closer"><span class="fa fa-arrow-left fs-16"></span></a>
    </div>
    <div class="col-xs-11 pt-lg text-center">
        <h4><?php echo $view['translator']->trans($canvasContent['header']); ?></h4>
    </div>

    <?php elseif ($canvas == 'Main'): ?>
        <?php if ($hasLeft): ?>
            <?php $icon = ($canvasContent['header'] != 'mautic.core.settings') ? 'fa-gears' : 'fa-arrow-left'; ?>
            <div class="col-xs-1 pt-lg text-left">
                <a href="javascript:void(0);" class="offcanvas-opener offcanvas-open-ltr"><span class="fa <?php echo $icon; ?> fs-16"></span></a>
            </div>
        <?php endif; ?>

        <div class="<?php echo $class; ?> pt-lg text-center">
            <h4><?php echo $view['translator']->trans($canvasContent['header']); ?></h4>
        </div>

        <?php if ($hasRight): ?>
            <div class="col-xs-1 pt-lg text-right">
                <a href="javascript:void(0);" class="offcanvas-opener offcanvas-open-rtl"><span class="fa fa-arrow-right fs-16"></span></a>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- start: loading bar -->
    <div class="canvas-loading-bar">
        <?php echo $view['translator']->trans('mautic.core.loading'); ?>
    </div>
    <!--/ end: loading bar -->
</div>
<!--/ end: sidebar header -->

<?php if (!empty($canvasContent['footer'])): ?>
<!-- start: sidebar footer -->
    <div class="sidebar-footer box-layout" id="OffCanvas<?php echo $canvas; ?>Footer">
        <?php echo $canvasContent['footer']; ?>
    </div>
<!--/ end: sidebar footer -->
<?php endif; ?>

<!-- start: sidebar content -->
<div class="sidebar-content">
    <!-- scroll-content -->
    <div class="scroll-content slimscroll" id="OffCanvas<?php echo $canvas; ?>Content">
        <?php echo $canvasContent['content']; ?>
    </div>
</div>
<!--/ end: sidebar content -->