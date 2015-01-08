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
<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('top1'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('top2'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('top3'); ?></div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-2">
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('left1'); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('left2'); ?>
                </div>
            </div>


            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('left3'); ?>
                </div>
            </div>
        </div>

        <div class="col-xs-12 col-sm-8">
            <?php $view['slots']->output('main'); ?>
        </div>

        <div class="col-xs-12 col-sm-2">
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('right1'); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('right2'); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <?php $view['slots']->output('right3'); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="main-block bg-primary row">
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom1'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom2'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom3'); ?></div>
    </div>

    <div class="row">
        <div class="col-xs-12"><?php $view['slots']->output('footer'); ?></div>
    </div>
    
</div>
<div class="copyright text-center">
        <small>Background <a href="https://flic.kr/p/ppk9JE" alt="Narrow river">photo</a> by Christopher Michel</small>
    </div>
<?php $view['slots']->output('builder'); ?>