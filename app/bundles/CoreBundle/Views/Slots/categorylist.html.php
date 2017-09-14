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
<div class="codemodeHtmlContainer">
    <div class="global-categories text-left">
        <div>
            <label class="control-label"><?php echo $view['translator']->trans('mautic.lead.form.categories'); ?></label>
        </div>
        <div id="category-1" class="text-left">
            <input type="checkbox" id="lead_contact_frequency_rules_global_categories_1" name="lead_contact_frequency_rules[global_categories][]" autocomplete="false" value="1" checked="checked">
            <label for="lead_contact_frequency_rules_global_categories_1"><?php echo  $view['translator']->trans('mautic.core.category'); ?></label>
        </div>
    </div>
</div>
