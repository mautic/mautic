<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<!-- start: loading bar -->
<div class="loading-bar">
    <?php echo $view['translator']->trans('mautic.core.loading'); ?>
</div>
<!--/ end: loading bar -->

<!-- start: navbar nocollapse -->
<div class="navbar-nocollapse">
    <!-- start: Sidebar left toggle -->
    <div class="navbar-header navbar-left visible-xs-inline-block">
        <button type="button" class="navbar-toggle" data-toggle="sidebar" data-direction="ltr">
            <span class="icon-bar thin"></span>
            <span class="icon-bar thin"></span>
            <span class="icon-bar thin"></span>
        </button>
    </div>
    <!--/ end: Sidebar left toggle -->

    <!-- start: Sidebar right toggle -->
    <div class="navbar-header navbar-right">
        <button type="button" class="navbar-toggle" data-toggle="sidebar" data-direction="rtl">
            <span class="icon-bar thin"></span>
            <span class="icon-bar thin"></span>
            <span class="icon-bar thin"></span>
        </button>
    </div>
    <!--/ end: Sidebar right toggle -->

    <!-- start: left nav -->
    <ul class="nav navbar-nav navbar-left">
        <li class="hidden-xs" data-toggle="tooltip" data-placement="right" title="Minimize Sidebar">
            <a href="javascript:void(0)" data-toggle="minimize" class="sidebar-minimizer"><span class="arrow fs-14"></span></a>
        </li>
        <?php echo $view['actions']->render(new \Symfony\Component\HttpKernel\Controller\ControllerReference('MauticCoreBundle:Default:notifications')); ?>
        <?php echo $view['actions']->render(new \Symfony\Component\HttpKernel\Controller\ControllerReference('MauticCoreBundle:Default:globalSearch')); ?>
    </ul>
    <!--/ end: left nav -->

    <!-- start: right nav -->
    <ul class="nav navbar-nav navbar-right">
        <?php echo $view->render("MauticCoreBundle:Menu:profile.html.php"); ?>
    </ul>
    <!--/ end: right nav -->
</div>
<!--/ end: navbar nocollapse -->