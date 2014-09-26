<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<!-- start: loading bar -->
<div class="loading-bar">
    <?php echo $view['translator']->trans('mautic.core.loading'); ?>
</div>
<!--/ end: loading bar -->

<!-- start: container fluid -->
<div class="container-fluid">
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

        <!-- start: right nav -->
        <ul class="nav navbar-nav navbar-right">
            <?php echo $view['knp_menu']->render('admin', array("menu" => "admin")); ?>
            <?php echo $view->render("MauticCoreBundle:Menu:profile.html.php"); ?>
        </ul>
        <!--/ end: right nav -->
    </div>
    <!--/ end: navbar nocollapse -->
</div>
<!--/ end: container fluid -->