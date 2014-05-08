<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<input type="search"
       class="form-control filter <?php echo (!empty($filterValue) ? 'show-filter' : 'hide-filter'); ?>"
       id="list-filter" name="<?php echo $filterName; ?>" type="text"
       placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>"
       value="<?php echo $filterValue; ?>"
       onkeypress="Mautic.filterList(event, '<?php echo $filterUri; ?>');"
       onmouseover="Mautic.showFilterInput()"
       onmouseout="Mautic.hideFilterInput();"
       onblur="Mautic.hideFilterInput();"
       autocomplete="off"
       data-toggle="tooltip"
       data-container="body"
       data-placement="bottom"
       data-original-title="<?php echo $view['translator']->trans('mautic.core.search.help') .
           (!empty($filterTooltip) ? $view['translator']->trans($filterTooltip) : ""); ?>"
    />