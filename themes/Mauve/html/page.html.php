<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend(":$template:base.html.php");
$parentVariant = $page->getVariantParent();
$title         = (!empty($parentVariant)) ? $parentVariant->getTitle() : $page->getTitle();
$view['slots']->set('pageTitle', $title);
?>
<!-- Header Carousel -->
<header id="myCarousel" class="carousel slide">
    <!-- Indicators -->
    <ol class="carousel-indicators">
        <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#myCarousel" data-slide-to="1"></li>
        <li data-target="#myCarousel" data-slide-to="2"></li>
    </ol>

    <!-- Wrapper for slides -->
    <div class="carousel-inner">
        <div class="item active">
            <div class="fill" style="background-image:url('http://placehold.it/1900x1080&text=Slide One');"></div>
            <div class="carousel-caption">
                <h2>Caption 1</h2>
            </div>
        </div>
        <div class="item">
            <div class="fill" style="background-image:url('http://placehold.it/1900x1080&text=Slide Two');"></div>
            <div class="carousel-caption">
                <h2>Caption 2</h2>
            </div>
        </div>
        <div class="item">
            <div class="fill" style="background-image:url('http://placehold.it/1900x1080&text=Slide Three');"></div>
            <div class="carousel-caption">
                <h2>Caption 3</h2>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <a class="left carousel-control" href="#myCarousel" data-slide="prev">
        <span class="icon-prev"></span>
    </a>
    <a class="right carousel-control" href="#myCarousel" data-slide="next">
        <span class="icon-next"></span>
    </a>
</header>

<!-- Page Content -->
<div class="container">

    <!-- Marketing Icons Section -->
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <?php $view['slots']->output('page_title'); ?>
            </h1>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><?php $view['slots']->output('top1_title'); ?></h4>
                </div>
                <div class="panel-body">
                    <?php $view['slots']->output('top1'); ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><?php $view['slots']->output('top2_title'); ?></h4>
                </div>
                <div class="panel-body">
                    <?php $view['slots']->output('top2'); ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><?php $view['slots']->output('top3_title'); ?></h4>
                </div>
                <div class="panel-body">
                    <?php $view['slots']->output('top3'); ?>
                </div>
            </div>
        </div>
    </div>
    <!-- /.row -->

    <!-- Portfolio Section -->
    <div class="row">
        <div class="col-lg-12">
            <h2 class="page-header"><?php $view['slots']->output('section1_title'); ?></h2>
        </div>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio1'); ?>
        </div>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio2'); ?>
        </div>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio3'); ?>
        </div>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio4'); ?>
        </div>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio5'); ?>
        </div>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio6'); ?>
        </div>
    </div>
    <!-- /.row -->

    <!-- Features Section -->
    <div class="row">
        <div class="col-lg-12">
            <h2 class="page-header"><?php $view['slots']->output('section2_title'); ?></h2>
        </div>
        <div class="col-md-6">
            <?php $view['slots']->output('section2'); ?>
        </div>
        <div class="col-md-6">
            <?php $view['slots']->output('section2_graphic'); ?>
        </div>
    </div>
    <!-- /.row -->

    <hr>

    <!-- Call to Action Section -->
    <div class="well">
        <div class="row">
            <div class="col-md-8">
                <?php $view['slots']->output('cta'); ?>
            </div>
            <div class="col-md-4">
                <?php $view['slots']->output('cta_button'); ?>
            </div>
        </div>
    </div>

    <hr>

    <!-- Footer -->
    <footer>
        <div class="row">
            <div class="col-lg-12">
                <?php $view['slots']->output('footer'); ?>
            </div>
        </div>
    </footer>

</div>
<!-- /.container -->
<?php $view['slots']->output('builder'); ?>