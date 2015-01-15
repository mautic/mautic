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

<?php if ($view['slots']->hasContent(array('top1', 'top2', 'top3'))): ?>
<div id="header">
    <div class="container">
        <div class="row">
            <?php if ($view['slots']->hasContent('top1')): ?>
            <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('top1'); ?></div>
            <?php endif; // end of top1 ?>
            <?php if ($view['slots']->hasContent('top2')): ?>
            <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('top2'); ?></div>
            <?php endif; // end of top2 ?>
            <?php if ($view['slots']->hasContent('top3')): ?>
            <div class="col-xs-12 col-sm-4 pull-right"><?php $view['slots']->output('top3'); ?></div>
            <?php endif; // end of top3 ?>
        </div>
    </div>
</div>
<?php endif; // end of header check ?>

<?php if ($view['slots']->hasContent(array('left1', 'left2', 'left3', 'right1', 'right2', 'right3'))): ?>
<div id="content" class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <?php if ($view['slots']->hasContent('left1')): ?>
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('left1'); ?>
                </div>
            </div>
            <?php endif; // end of left1 ?>

            <?php if ($view['slots']->hasContent('left2')): ?>
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('left2'); ?>
                </div>
            </div>
            <?php endif; // end of left2 ?>

            <?php if ($view['slots']->hasContent('left3')): ?>
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('left3'); ?>
                </div>
            </div>
            <?php endif; // end of left3 ?>
        </div>

        <div class="col-xs-12 col-sm-6">
            <?php if ($view['slots']->hasContent('right1')): ?>
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('right1'); ?>
                </div>
            </div>
            <?php endif; // end of right1 ?>

            <?php if ($view['slots']->hasContent('right2')): ?>
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('right2'); ?>
                </div>
            </div>
            <?php endif; // end of right2 ?>

            <?php if ($view['slots']->hasContent('right3')): ?>
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('right3'); ?>
                </div>
            </div>
            <?php endif; // end of right3 ?>
        </div>
    </div>
</div>
<?php endif; // end of content check ?>

<?php if ($view['slots']->hasContent(array('bottom1', 'bottom2', 'bottom3'))): ?>
<div id="footer">
    <div class="container">
        <div class="row">
            <?php if ($view['slots']->hasContent('bottom1')): ?>
            <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom1'); ?></div>
            <?php endif; // end of bottom1 ?>
            <?php if ($view['slots']->hasContent('bottom2')): ?>
            <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom2'); ?></div>
            <?php endif; // end of bottom2 ?>
            <?php if ($view['slots']->hasContent('bottom3')): ?>
            <div class="col-xs-12 col-sm-4 pull-right"><?php $view['slots']->output('bottom3'); ?></div>
            <?php endif; // end of bottom3 ?>
        </div>
    </div>
</div>
<?php endif; // end of footer check ?>

<?php if ($view['slots']->hasContent('footer')): ?>
<div id="copyright">
    <div class="container">
        <div class="row">
            <div class="col-xs-12"><?php $view['slots']->output('footer'); ?></div>
        </div>
    </div>
</div>
<?php endif; // end of footer ?>

<?php $view['slots']->output('builder'); ?>