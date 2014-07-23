<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend(':Templates/'.$template.':base.html.php');

$view['blocks']->set('pageTitle', $page->getTitle());
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-xs-12 col-sm-4"><?php $view['blocks']->output('top1'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['blocks']->output('top2'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['blocks']->output('top3'); ?></div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-2">
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['blocks']->output('left1'); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <?php $view['blocks']->output('left2'); ?>
                </div>
            </div>


            <div class="row">
                <div class="col-xs-12">
                    <?php $view['blocks']->output('left3'); ?>
                </div>
            </div>
        </div>

        <div class="col-xs-12 col-sm-8">
            <?php $view['blocks']->output('main'); ?>
        </div>

        <div class="col-xs-12 col-sm-2">
            <div class="row">
                <div class="col-xs-12">
                    <?php $view['blocks']->output('right1'); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <?php $view['blocks']->output('right2'); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <?php $view['blocks']->output('right3'); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-4"><?php $view['blocks']->output('bottom1'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['blocks']->output('bottom2'); ?></div>
        <div class="col-xs-12 col-sm-4"><?php $view['blocks']->output('bottom3'); ?></div>
    </div>

    <div class="row">
        <div class="col-xs-12"><?php $view['blocks']->output('footer'); ?></div>
    </div>
</div>
<?php $view['blocks']->output('builder'); ?>