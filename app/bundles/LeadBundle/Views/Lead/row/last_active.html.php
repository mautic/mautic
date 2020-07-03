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
    <abbr title="<?php echo $view['date']->toFull($item->getLastActive()); ?>">
        <?php echo $view['date']->toText($item->getLastActive()); ?>
    </abbr>
</td>