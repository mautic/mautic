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
    if (isset($fields['core'][$column]['value'])) {
        echo $view->escape($fields['core'][$column]['value']);
    }
    if (isset($fields['social'][$column]['value'])) {
        echo $view->escape($fields['social'][$column]['value']);
    }
    ?>
</td>
