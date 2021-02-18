<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!isset($editButtonIcon)) {
    $editButtonIcon = 'fa-pencil-square-o text-primary';
}

if (!isset($deleteButtonIcon)) {
    $deleteButtonIcon = 'fa-trash-o text-danger';
}

?>

<div class="sortable-panel-buttons btn-group" role="group" aria-label="Actions">
    <button type="button" class="btn btn-default btn-edit btn-nospin">
        <i class="fa <?php echo $editButtonIcon; ?>"></i>
    </button>
    <?php if (empty($disallowDelete)): ?>
    <button type="button" class="btn btn-default btn-delete btn-nospin">
        <i class="fa <?php echo $deleteButtonIcon; ?>"></i>
    </button>
    <?php endif; ?>
</div>