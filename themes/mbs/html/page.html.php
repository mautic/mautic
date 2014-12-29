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
<!-- Page Content -->
    <div class="container">

        <!-- Jumbotron Header -->
        <header class="jumbotron hero-spacer">
            <h1><?php $view['slots']->output('page_title'); ?></h1>
            <?php $view['slots']->output('header'); ?>
        </header>

        <hr>

        <!-- Title -->
        <div class="row">
            <div class="col-lg-12">
                <h3><?php $view['slots']->output('section_title'); ?></h3>
            </div>
        </div>
        <!-- /.row -->

        <!-- Page Features -->
        <div class="row text-center">

            <div class="col-md-3 col-sm-6 hero-feature">
                <div class="thumbnail">
                    <?php $view['slots']->output('graphic_1'); ?>
                    <div class="caption">
                        <h3><?php $view['slots']->output('graphic_1_title'); ?></h3>
                        <?php $view['slots']->output('graphic_1_body'); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 hero-feature">
                <div class="thumbnail">
                    <?php $view['slots']->output('graphic_2'); ?>
                    <div class="caption">
                        <h3><?php $view['slots']->output('graphic_2_title'); ?></h3>
                        <?php $view['slots']->output('graphic_2_body'); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 hero-feature">
                <div class="thumbnail">
                    <?php $view['slots']->output('graphic_3'); ?>
                    <div class="caption">
                        <h3><?php $view['slots']->output('graphic_3_title'); ?></h3>
                        <?php $view['slots']->output('graphic_3_body'); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 hero-feature">
                <div class="thumbnail">
                    <?php $view['slots']->output('graphic_4'); ?>
                    <div class="caption">
                        <h3><?php $view['slots']->output('graphic_4_title'); ?></h3>
                        <?php $view['slots']->output('graphic_4_body'); ?>
                    </div>
                </div>
            </div>

        </div>
        <!-- /.row -->

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