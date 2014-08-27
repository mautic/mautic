<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<!-- start: Brand and toggle -->
<div class="navbar-header">
    <!-- sidebar toggle button -->
    <button type="button" class="navbar-toggle" sidebar-toggle data-position="left">
        <span class="icon-bar thin"></span>
        <span class="icon-bar thin"></span>
        <span class="icon-bar thin"></span>
    </button>
    <!--/ sidebar toggle button -->

    <!-- brand -->
    <a class="navbar-brand" href="#">{{app.name}}</a>
    <!--/ brand -->
</div>
<!--/ end: Brand and toggle -->

<!-- start: right nav -->
<ul class="nav navbar-nav navbar-right">
    <!--<li class="dropdown">
        <a href="" class="dropdown-toggle" data-toggle="dropdown">
            <span class="img-wrapper img-rounded"><img src="images/avatar/avatar2.jpg" alt=""></span>
            <span class="text fw-sb ml5 hidden-xs">Andrew</span>
            <span class="caret ml5"></span>
        </a>
        <ul class="dropdown-menu" role="menu">
            <li><a href="#">Action</a></li>
            <li><a href="#">Another action</a></li>
            <li><a href="#">Something else here</a></li>
            <li class="divider"></li>
            <li><a href="#">Separated link</a></li>
        </ul>
    </li>-->

    <?php echo $view['knp_menu']->render('admin', array("menu" => "admin")); ?>
    <?php echo $view->render("MauticCoreBundle:Menu:profile.html.php"); ?>
</ul>
<!--/ end: right nav -->
</div>