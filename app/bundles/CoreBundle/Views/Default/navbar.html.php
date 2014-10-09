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
    </ul>
    <!--/ end: left nav -->

    <!-- start: right nav -->
    <ul class="nav navbar-nav navbar-right">
        <?php echo $view['knp_menu']->render('admin', array("menu" => "admin")); ?>
        <li class="dropdown dropdown-custom">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <span class="label label-danger">3</span>
                <span class="fa fa-bell fs-16"></span>
            </a>
            <div class="dropdown-menu">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="panel-title"><h6 class="fw-sb">3 new notification</h6></div>
                    </div>
                    <div class="pt-xs pb-xs pl-0 pr-0">
                        <div class="scroll-content slimscroll" style="height:250px;">
                            <div class="media pt-sm pb-sm pr-md pl-md nm bdr-b">
                                <span class="pull-left img-wrapper img-rounded mt-xs" style="width:36px">
                                    <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/mlane/73.jpg">
                                </span>
                                <div class="media-body">
                                    <a href="" class="media-heading fw-sb mb-0 text-primary">Michale Lane Lunch complete a task</a>
                                    <div class="ellipsis text-white dark-sm">Nam porttitor scelerisque neque. Nullam nisl. Maecenas malesuada</div>
                                    <div class="clearfix mt-xs">
                                        <span class="fa fa-check text-success pull-left mr-xs"></span>
                                        <span class="fs-10 text-white dark-sm pull-left">1H</span>
                                    </div>
                                </div>
                            </div>
                            <div class="media pt-sm pb-sm pr-md pl-md nm bdr-b">
                                <span class="pull-left mt-xs" style="width:36px">
                                    <span class="fa fa-newspaper-o text-warning fs-20"></span>
                                </span>
                                <div class="media-body">
                                    <a href="" class="media-heading fw-sb mb-0 text-primary">Your campaign is completed!</a>
                                    <div class="ellipsis text-white dark-sm">Nam porttitor scelerisque neque. Nullam nisl. Maecenas malesuada</div>
                                    <div class="clearfix mt-xs">
                                        <span class="fs-10 text-white dark-sm pull-left">2H</span>
                                    </div>
                                </div>
                            </div>
                            <div class="media pt-sm pb-sm pr-md pl-md nm bdr-b">
                                <span class="pull-left img-wrapper img-rounded mt-xs" style="width:36px">
                                    <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/BillSKenney/73.jpg">
                                </span>
                                <div class="media-body">
                                    <a href="" class="media-heading fw-sb mb-0 text-primary">Bill Kenny join the conference.</a>
                                    <div class="ellipsis text-white dark-sm">Nam porttitor scelerisque neque. Nullam nisl. Maecenas malesuada</div>
                                    <div class="clearfix mt-xs">
                                        <span class="fa fa-thumbs-up text-success pull-left mr-xs"></span>
                                        <span class="fs-10 text-white dark-sm pull-left">1D</span>
                                    </div>
                                </div>
                            </div>
                            <div class="media pt-sm pb-sm pr-md pl-md nm">
                                <span class="pull-left mt-xs" style="width:36px">
                                    <span class="fa fa-trophy text-warning fs-20"></span>
                                </span>
                                <div class="media-body">
                                    <a href="" class="media-heading fw-sb mb-0 text-primary">You have a page winner!</a>
                                    <div class="ellipsis text-white dark-sm">Nam porttitor scelerisque neque. Nullam nisl. Maecenas malesuada</div>
                                    <div class="clearfix mt-xs">
                                        <span class="fs-10 text-white dark-sm pull-left">2H</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        <?php echo $view->render("MauticCoreBundle:Menu:profile.html.php"); ?>
    </ul>
    <!--/ end: right nav -->
</div>
<!--/ end: navbar nocollapse -->