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
<td class="<?php echo $class ?> text-center">
    <?php
    $color = $item->getColor();
    $style = !empty($color) ? ' style="background-color: '.$color.';"' : '';
    ?>
    <span class="label label-default"<?php echo $style; ?>><?php echo $item->getPoints(); ?></span>
</td>