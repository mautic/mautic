<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$template   = '<div class="col-md-6">{content}</div>';
$properties = (isset($form['properties'])) ? $form['properties'] : [];
?>

<div class="bundle-form">
    <div class="bundle-form-header">
        <h3><?php echo $actionHeader; ?></h3>
    </div>

    <?php echo $view['form']->start($form); ?>
    <div class="row pa-md">
        <?php echo $view['form']->row($form['name']); ?>
        <?php echo $view['form']->row($form['description']); ?>
        <?php echo $view['form']->row($form['properties']); ?>
    </div>
    <?php echo $view['form']->end($form); ?>
</div>