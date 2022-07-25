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
    <a href="<?php echo $view['router']->path('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $item->getId()]); ?>" data-toggle="ajax">
    <?php echo $item->getId(); ?>
    </a>
</td>
