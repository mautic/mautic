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
$view['slots']->set('public', (isset($public) && $public === true) ? true : false);
$view['slots']->set('pageTitle', $title);
?>
<!-- Header Carousel -->
<div id="carousel-generic" class="carousel slide" data-ride="carousel">
    <!-- Indicators -->
    <ol class="carousel-indicators">
        <li data-target="#carousel-generic" data-slide-to="0" class="active"></li>
        <li data-target="#carousel-generic" data-slide-to="1"></li>
        <li data-target="#carousel-generic" data-slide-to="2"></li>
    </ol>

    <!-- Wrapper for slides -->
    <div class="carousel-inner" role="listbox">
        <div class="item text-center active">
            <img src="http://placehold.it/1900x800/4e5d9d&text=Slide+One" alt="Slide One" />
            <div class="carousel-caption">
                <h2>Caption 1</h2>
            </div>
        </div>
        <div class="item text-center">
            <img src="http://placehold.it/1900x800/4e5d9d&text=Slide+Two" alt="Slide Two" />
            <div class="carousel-caption">
                <h2>Caption 2</h2>
            </div>
        </div>
        <div class="item text-center">
            <img src="http://placehold.it/1900x800/4e5d9d&text=Slide+Three" alt="Slide Three" />
            <div class="carousel-caption">
                <h2>Caption 3</h2>
            </div>
        </div>
    </div>
</div>

<!-- Page Content -->
<div class="container">

    <?php if ($view['slots']->hasContent(array('page_title', 'top1_title', 'top1', 'top2_title', 'top2', 'top3_title', 'top3'))): ?>
    <!-- Marketing Icons Section -->
    <div class="row">
        <?php if ($view['slots']->hasContent('page_title')): ?>
        <div class="col-lg-12">
            <h1 class="page-header">
                <?php $view['slots']->output('page_title'); ?>
            </h1>
        </div>
        <?php endif; // end of page_title ?>
        <?php if ($view['slots']->hasContent(array('top1_title', 'top1'))): ?>
        <div class="col-md-4">
            <div class="panel panel-default">
                <?php if ($view['slots']->hasContent('top1_title')): ?>
                <div class="panel-heading">
                    <h4><?php $view['slots']->output('top1_title'); ?></h4>
                </div>
                <?php endif; // end of top1_title ?>
                <?php if ($view['slots']->hasContent('top1')): ?>
                <div class="panel-body">
                    <?php $view['slots']->output('top1'); ?>
                </div>
                <?php endif; // end of top1 ?>
            </div>
        </div>
        <?php endif; // end of top1 section ?>
        <?php if ($view['slots']->hasContent(array('top2_title', 'top2'))): ?>
        <div class="col-md-4">
            <div class="panel panel-default">
                <?php if ($view['slots']->hasContent('top2_title')): ?>
                <div class="panel-heading">
                    <h4><?php $view['slots']->output('top2_title'); ?></h4>
                </div>
                <?php endif; // end of top2_title ?>
                <?php if ($view['slots']->hasContent('top2')): ?>
                <div class="panel-body">
                    <?php $view['slots']->output('top2'); ?>
                </div>
                <?php endif; // end of top2 ?>
            </div>
        </div>
        <?php endif; // end of top2 section ?>
        <?php if ($view['slots']->hasContent(array('top3_title', 'top3'))): ?>
        <div class="col-md-4">
            <div class="panel panel-default">
                <?php if ($view['slots']->hasContent('top3_title')): ?>
                <div class="panel-heading">
                    <h4><?php $view['slots']->output('top3_title'); ?></h4>
                </div>
                <?php endif; // end of top3_title ?>
                <?php if ($view['slots']->hasContent('top3')): ?>
                <div class="panel-body">
                    <?php $view['slots']->output('top3'); ?>
                </div>
                <?php endif; // end of top3 ?>
            </div>
        </div>
        <?php endif; // end of top3 section ?>
    </div>
    <!-- /.row -->
    <?php endif; // end of Marketing Icons check ?>

    <?php if ($view['slots']->hasContent(array('section1_title', 'portfolio1', 'portfolio2', 'portfolio3', 'portfolio4', 'portfolio5', 'portfolio6'))): ?>
    <!-- Portfolio Section -->
    <div class="row">
        <?php if ($view['slots']->hasContent('section1_title')): ?>
        <div class="col-lg-12">
            <h2 class="page-header"><?php $view['slots']->output('section1_title'); ?></h2>
        </div>
        <?php endif; // end of section1_title ?>
        <?php if ($view['slots']->hasContent('portfolio1')): ?>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio1'); ?>
        </div>
        <?php endif; // end of portfolio1 ?>
        <?php if ($view['slots']->hasContent('portfolio2')): ?>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio2'); ?>
        </div>
        <?php endif; // end of portfolio2 ?>
        <?php if ($view['slots']->hasContent('portfolio3')): ?>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio3'); ?>
        </div>
        <?php endif; // end of portfolio3 ?>
        <?php if ($view['slots']->hasContent('portfolio4')): ?>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio4'); ?>
        </div>
        <?php endif; // end of portfolio4 ?>
        <?php if ($view['slots']->hasContent('portfolio5')): ?>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio5'); ?>
        </div>
        <?php endif; // end of portfolio5 ?>
        <?php if ($view['slots']->hasContent('portfolio6')): ?>
        <div class="col-md-4 col-sm-6">
            <?php $view['slots']->output('portfolio6'); ?>
        </div>
        <?php endif; // end of portfolio6 ?>
    </div>
    <!-- /.row -->
    <?php endif; // end of portfolio check ?>

    <?php if ($view['slots']->hasContent(array('section2_title', 'section2', 'section2_graphic'))): ?>
    <!-- Features Section -->
    <div class="row">
        <?php if ($view['slots']->hasContent('section2_title')): ?>
        <div class="col-lg-12">
            <h2 class="page-header"><?php $view['slots']->output('section2_title'); ?></h2>
        </div>
        <?php endif; // end of section2_title ?>
        <?php if ($view['slots']->hasContent('section2')): ?>
        <div class="col-md-6">
            <?php $view['slots']->output('section2'); ?>
        </div>
        <?php endif; // end of section2 ?>
        <?php if ($view['slots']->hasContent('section2_graphic')): ?>
        <div class="col-md-6">
            <?php $view['slots']->output('section2_graphic'); ?>
        </div>
        <?php endif; // end of section2_graphic ?>
    </div>
    <!-- /.row -->
    <?php endif; // end of Features check ?>

    <hr>

    <?php if ($view['slots']->hasContent(array('cta', 'cta_button'))): ?>
    <!-- Call to Action Section -->
    <div class="well">
        <div class="row">
            <?php if ($view['slots']->hasContent('cta')): ?>
            <div class="col-md-8">
                <?php $view['slots']->output('cta'); ?>
            </div>
            <?php endif; // end of cta ?>
            <?php if ($view['slots']->hasContent('cta_button')): ?>
            <div class="col-md-4">
                <?php $view['slots']->output('cta_button'); ?>
            </div>
            <?php endif; // end of cta_button ?>
        </div>
    </div>
    <?php endif; // end of Call to Action check ?>

    <hr>

    <?php if ($view['slots']->hasContent('cta')): ?>
    <!-- Footer -->
    <footer>
        <div class="row">
            <div class="col-lg-12">
                <?php $view['slots']->output('footer'); ?>
            </div>
        </div>
    </footer>
    <?php endif; // end of footer ?>

</div>
<!-- /.container -->
<?php $view['slots']->output('builder'); ?>