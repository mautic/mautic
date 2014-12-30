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

<!-- Note: The background image is set within the business-casual.css file. -->
<header class="business-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php $view['slots']->output('header'); ?>
            </div>
        </div>
    </div>
</header>

<!-- Page Content -->
<div class="container">

    <hr>

    <div class="row">
        <div class="col-sm-8">
           <?php $view['slots']->output('top1'); ?>
        </div>
        <div class="col-sm-4">
            <?php $view['slots']->output('top2'); ?>
        </div>
    </div>
    <!-- /.row -->

    <hr>

    <div class="row">
        <div class="col-sm-4">
            <?php $view['slots']->output('mid1'); ?>
        </div>
        <div class="col-sm-4">
            <?php $view['slots']->output('mid2'); ?>
        </div>
        <div class="col-sm-4">
            <?php $view['slots']->output('mid3'); ?>
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
        <!-- /.row -->
    </footer>

</div>
<!-- /.container -->

<?php $view['slots']->output('builder'); ?>