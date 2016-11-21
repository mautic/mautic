<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!empty($deleted)):
    $action    = 'undelete';
    $iconClass = 'fa-undo';
    $btnClass  = 'btn-warning';
else:
    $action    = 'delete';
    $iconClass = 'fa-times';
    $btnClass  = 'btn-danger';
endif;

if (empty($route)) {
    $route = 'mautic_pointtriggerevent_action';
}
?>

<div class="form-buttons hide">
    <a data-toggle="ajaxmodal" data-target="#triggerEventModal" href="<?php echo $view['router']->path($route, ['objectAction' => 'edit', 'objectId' => $id, 'triggerId' => $sessionId]); ?>" class="btn btn-primary btn-xs btn-edit">
        <i class="fa fa-pencil-square-o"></i>
    </a>
    <a data-menu-link="mautic_point_index" data-toggle="ajax" data-ignore-formexit="true" data-method="POST" data-hide-loadingbar="true" href="<?php echo $view['router']->path($route, ['objectAction' => $action, 'objectId' => $id, 'triggerId' => $sessionId]); ?>"  class="btn <?php echo $btnClass; ?> btn-xs">
        <i class="fa <?php echo $iconClass; ?>"></i>
    </a>
    <i class="fa fa-fw fa-ellipsis-v reorder-handle"></i>
</div>