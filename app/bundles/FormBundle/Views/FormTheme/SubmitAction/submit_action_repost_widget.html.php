<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="row">
    <div class="col-sm-12">
        <?php echo $view['form']->row($form['post_url']); ?>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <?php echo $view['form']->row($form['failure_email']); ?>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <?php echo $view['form']->row($form['authorization_header']); ?>
    </div>
</div>

<?php unset($form['post_url'], $form['failure_email'], $form['authorization_header']); ?>

<h4><?php echo $view['translator']->trans('mautic.form.action.repost.field_mapping'); ?></h4>
<div class="row mt-lg">
    <?php foreach ($form as $child): ?>
    <div class="col-sm-6">
        <?php echo $view['form']->row($child); ?>
    </div>
    <?php endforeach; ?>
</div>