<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend(':Templates/'.$template.':base.html.php');

$view['slots']->set('pageTitle', $page->getTitle());
?>
<div class="container-fluid">
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

    <div class="row">
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom1'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom2'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['slots']->output('bottom3'); ?></div>
    </div>

    <div class="row">
        <div class="col-xs-12"><?php $view['slots']->output('footer'); ?></div>
    </div>
</div>
<?php $view['slots']->output('builder'); ?>