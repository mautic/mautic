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
<?php if (isset($form)) : ?>
    <?php if ($showContactCategories && isset($form['global_categories']) && count($form['global_categories'])):?>
<div class="global-categories text-left">
    <div>
        <label class="control-label"><?php echo $view['translator']->trans('mautic.lead.form.categories'); ?></label>
    </div>
        <?php $categoryNumber = count($form['global_categories']->vars['choices']);
        for ($i = ($categoryNumber - 1); $i >= 0; --$i): ?>
            <div id="category-<?php echo $i; ?>" class="text-left">
                <?php echo $view['form']->widget($form['global_categories'][$i]); ?>
                <?php echo $view['form']->label($form['global_categories'][$i]); ?>
            </div>
            <?php
        endfor;
        unset($form['global_categories']);
?>
    </div>
    <?php else:
        unset($form['global_categories']);
    endif;
else :
?>
    <div class="global-categories text-left">
        <div>
            <label class="control-label"><?php echo $view['translator']->trans('mautic.lead.form.categories'); ?></label>
        </div>
        <div id="category-1" class="text-left">
            <input type="checkbox" id="lead_contact_frequency_rules_global_categories_1" name="lead_contact_frequency_rules[global_categories][]" autocomplete="false" value="1" checked="checked">
            <label for="lead_contact_frequency_rules_global_categories_1"><?php echo  $view['translator']->trans('mautic.core.category'); ?></label>
        </div>
    </div>
<?php endif; ?>
