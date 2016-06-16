<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!empty($deleted)) {
    $action    = 'undelete';
    $iconClass = 'fa-undo';
    $btnClass  = 'btn-warning';
} else {
    $action    = 'delete';
    $iconClass = 'fa-times';
    $btnClass  = 'btn-danger';
}

if (empty($route)) {
    $route = 'mautic_formfield_action';
}

if (empty($actionType)) {
    $actionType = '';
} else {
    $actionType .= '_';
}
?>

<div class="form-buttons hide">
    <a data-toggle="ajaxmodal" data-target="#formComponentModal" href="<?php echo $view['router']->path($route, array('objectAction' => 'edit', 'objectId' => $id, 'formId' => $formId)); ?>" class="btn btn-primary btn-xs btn-edit">
        <i class="fa fa-pencil-square-o"></i>
    </a>
    <?php if (empty($disallowDelete)): ?>
    <a data-menu-link="mautic_form_index" data-toggle="ajax" data-target="#mauticform_<?php echo $actionType . $id; ?>" data-ignore-formexit="true" data-method="POST" data-hide-loadingbar="true" href="<?php echo $view['router']->path($route, array('objectAction' => $action, 'objectId' => $id, 'formId' => $formId)); ?>"  class="btn <?php echo $btnClass; ?> btn-xs">
        <i class="fa <?php echo $iconClass; ?>"></i>
    </a>
    <?php endif; ?>
    <i class="fa fa-fw fa-ellipsis-v reorder-handle"></i>
</div>