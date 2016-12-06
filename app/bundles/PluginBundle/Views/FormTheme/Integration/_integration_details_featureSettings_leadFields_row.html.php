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

<div class="row" id="leadFieldsContainer">
    <?php if (!empty($specialInstructions)): ?>
        <div class="alert alert-<?php echo $alertType; ?>">
            <?php echo $view['translator']->trans($specialInstructions); ?>
        </div>
    <?php endif; ?>
    <div class="form-group col-xs-12">
        <?php echo $view['form']->errors($form); ?>
        <?php $rowCount = 1; ?>
        <?php foreach ($form->children as $child): ?>
        <?php if ($rowCount++ % 2 == 1): ?>
        <div class="row">
        <?php endif; ?>
            <div class="col-sm-6">
                <?php echo $view['form']->row($child); ?>
            </div>
        <?php if ($rowCount++ % 2 == 1): ?>
        </div>
        <?php endif; ?>
        <?php ++$rowCount; ?>
        <?php endforeach; ?>
        <?php
        if ($rowCount % 2 == 0):?>
    </div>
        <?php endif; ?>
    </div>
</div>