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

<div class="ml-lg mr-lg mt-md pa-lg">
    <?php echo $view['form']->start($form); ?>
    <div class="panel panel-info">
        <div class="panel-heading">
            <div class="panel-title"><?php echo $view['translator']->trans('mautic.lead.import.default.owner'); ?></div>
        </div>
        <div class="panel-body">
            <div class="col-xs-4">
                <?php echo $view['form']->rowIfExists($form, 'owner'); ?>
            </div>
            <div class="col-xs-4">
                <?php echo $view['form']->rowIfExists($form, 'list'); ?>
            </div>
            <div class="col-xs-4">
                <?php echo $view['form']->rowIfExists($form, 'tags'); ?>
            </div>
        </div>
    </div>
    <div class="panel panel-info">
        <div class="panel-heading">
            <div class="panel-title"><?php echo $view['translator']->trans('mautic.lead.import.fields'); ?></div>
        </div>
        <div class="panel-body">
            <?php echo $view['form']->errors($form); ?>
            <?php $rowCount = 2; ?>
            <?php foreach ($form->children as $key => $child): ?>
                <?php if ($key != 'properties'): ?>
                    <?php if ($rowCount++ % 3 == 1): ?>
                        <div class="row">
                    <?php endif; ?>
                    <div class="col-sm-4">
                        <?php echo $view['form']->row($child); ?>
                    </div>
                    <?php if ($rowCount++ % 3 == 1): ?>
                        </div>
                    <?php endif; ?>
                    <?php ++$rowCount; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="panel panel-info">
        <div class="panel-heading">
            <div class="panel-title"><?php echo $view['translator']->trans('mautic.lead.import.properties'); ?></div>
        </div>
        <div class="panel-body">
            <?php $rowCount = 2; ?>
            <?php foreach ($form->children['properties'] as $child): ?>
                <?php if ($rowCount++ % 3 == 1): ?>
                    <div class="row">
                <?php endif; ?>
                <div class="col-sm-4">
                    <?php echo $view['form']->row($child); ?>
                </div>
                <?php if ($rowCount++ % 3 == 1): ?>
                    </div>
                <?php endif; ?>
                <?php ++$rowCount; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php echo $view['form']->end($form); ?>
</div>
