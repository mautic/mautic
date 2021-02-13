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
<td class="<?php echo $class; ?>">
    <?php
    foreach ($fields as $field) {
        if (isset($field[$column]['value'])) {
            echo $view->escape($field[$column]['value']);
        }
    }
    ?>
</td>
