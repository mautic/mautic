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
<div class="container">
<?php if ($view['slots']->hasContent(array('top1', 'top2', 'top1', 'top3'))): ?>
    <div class="row">
    <?php if ($view['slots']->hasContent('top1')): ?>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('top1'); ?></div>
    <?php endif; // end of top1 ?>
    <?php if ($view['slots']->hasContent('top2')): ?>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('top2'); ?></div>
    <?php endif; // end of top2 ?>
    <?php if ($view['slots']->hasContent('top3')): ?>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('top3'); ?></div>
    <?php endif; // end of top3 ?>
    </div>
<?php endif; // end of Top check ?>

<?php if ($view['slots']->hasContent(array('right3', 'right2', 'right1', 'main', 'left3', 'left2', 'left1'))): ?>
    <div class="row">
        <?php if ($view['slots']->hasContent(array('left3', 'left2', 'left1'))): ?>
        <div class="col-xs-12 col-sm-2">
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
        <?php endif; // end of Left check ?>

        <?php if ($view['slots']->hasContent('main')): ?>
        <div class="col-xs-12 col-sm-8">
            <?php $view['slots']->output('main'); ?>
        </div>
        <?php endif; // end of main ?>

        <?php if ($view['slots']->hasContent(array('right3', 'right2', 'right1'))): ?>
        <div class="col-xs-12 col-sm-2">
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
    <?php endif; // end of Right check ?>
    </div>
    <?php endif; // end of Center check ?>

    <?php if ($view['slots']->hasContent(array('bottom1', 'bottom2', 'bottom3'))): ?>
    <div class="main-block bg-primary row">
        <?php if ($view['slots']->hasContent('bottom1')): ?>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom1'); ?></div>
        <?php endif; // end of bottom1 ?>
        <?php if ($view['slots']->hasContent('bottom2')): ?>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom2'); ?></div>
        <?php endif; // end of bottom2 ?>
        <?php if ($view['slots']->hasContent('bottom3')): ?>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom3'); ?></div>
        <?php endif; // end of bottom3 ?>
    </div>
    <?php endif; // end of Bottom check ?>

    <?php if ($view['slots']->hasContent('footer')): ?>
    <div class="row">
        <div class="col-xs-12"><?php $view['slots']->output('footer'); ?></div>
    </div>
    <?php endif; // end of footer ?>
    
</div>
<div class="copyright text-center">
        <small>Background <a href="https://flic.kr/p/ppk9JE" alt="Narrow river">photo</a> by Christopher Michel</small>
    </div>
<?php $view['slots']->output('builder'); ?>