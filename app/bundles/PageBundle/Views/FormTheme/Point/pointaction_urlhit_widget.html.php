<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="row">
    <div class="col-xs-12">
        <?php echo $view['form']->row($form['page_url']); ?>
    </div>
</div>

<div class="row">
    <div class="col-xs-6">
        <?php echo $view['form']->row($form['page_hits']); ?>
    </div>
    <div class="col-xs-6">
        <?php echo $view['form']->row($form['first_time']); ?>
    </div>
</div>

<div class="row">
    <div class="col-xs-6">
        <?php echo $view['form']->row($form['returns_within']); ?>
    </div>
    <div class="col-xs-6">
        <?php echo $view['form']->row($form['returns_after']); ?>
    </div>
    <div class="col-xs-6">
        <?php echo $view['form']->row($form['accumulative_time']); ?>
    </div>
</div>