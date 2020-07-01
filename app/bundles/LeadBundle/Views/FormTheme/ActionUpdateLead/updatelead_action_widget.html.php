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
<?php if (isset($form->children['fields_to_update'])): ?>
    <?php echo $view['form']->row($form->children['fields_to_update']); ?>
    <?php unset($form->children['fields_to_update']); ?>
<?php endif; ?>
<div class="row">
<?php foreach ($form->children['fields'] as $alias => $child): ?>
    <div class="col-xs-12 update-contact-row mb-10">
        <div class="row">
            <div  id="<?php echo $alias; ?>_label" data-show-on='{"campaignevent_properties_fields_to_update":"<?php echo $alias; ?>"}'></div>
            <label class="mt-5 col-xs-3"><?php echo $child->vars['label']; ?></label>
            <div class="col-xs-4 contact-update-action">
                <?php echo $view['form']->widget($form->children['actions'][$alias]); ?>
            </div>
        <div class="col-xs-5 contact-update-input">
            <?php echo $view['form']->widget($child); ?>
        </div>
        </div>
    </div>
<?php endforeach; ?>
</div>