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
    <div class="container" style="margin-top: 50px;">

        <!-- Heading Row -->
        <div class="row">
            <div class="col-md-8">
                <?php $view['slots']->output('top1'); ?>
            </div>
            <!-- /.col-md-8 -->
            <div class="col-md-4">
                <?php $view['slots']->output('top2'); ?>
            </div>
            <!-- /.col-md-4 -->
        </div>
        <!-- /.row -->

        <hr>

        <!-- Call to Action Well -->
        <div class="row">
            <div class="col-lg-12">
                <div class="well text-center">
                    <?php $view['slots']->output('cta'); ?>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->

        <!-- Content Row -->
        <div class="row">
            <div class="col-md-4">
                <?php $view['slots']->output('mid1'); ?>
            </div>
            <!-- /.col-md-4 -->
            <div class="col-md-4">
                <?php $view['slots']->output('mid2'); ?>
            </div>
            <!-- /.col-md-4 -->
            <div class="col-md-4">
                <?php $view['slots']->output('mid3'); ?>
            </div>
            <!-- /.col-md-4 -->
        </div>
        <!-- /.row -->

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