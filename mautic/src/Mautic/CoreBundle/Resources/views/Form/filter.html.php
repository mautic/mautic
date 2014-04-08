<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<input class="form-control filter <?php echo (!empty($filterValue) ? 'show-filter' : 'hide-filter'); ?>"
       id="list-filter" name="<?php echo $filterName; ?>" maxlength="64" type="text"
       placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>"
       value="<?php echo $view->escape($filterValue); ?>"
       onkeypress="Mautic.filterList(event, '<?php echo $filterUri; ?>');"
       onmouseover="Mautic.showFilterInput()"
       onmouseout="Mautic.hideFilterInput();"
       onblur="Mautic.hideFilterInput();"
    />