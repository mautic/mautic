<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<td class="<?php echo $class ?>">
    <?php
    $flag = (!empty($fields['core']['country'])) ? $view['assets']->getCountryFlag($fields['core']['country']['value']) : '';
    if (!empty($flag)):
        ?>
        <img src="<?php echo $flag; ?>" style="max-height: 24px;" class="mr-sm" />
    <?php
    endif;
    $location = [];
    if (!empty($fields['core']['city']['value'])):
        $location[] = $fields['core']['city']['value'];
    endif;
    if (!empty($fields['core']['state']['value'])):
        $location[] = $fields['core']['state']['value'];
    elseif (!empty($fields['core']['country']['value'])):
        $location[] = $fields['core']['country']['value'];
    endif;
    echo $view->escape(implode(', ', $location));
    ?>
    <div class="clearfix"></div>
</td>
