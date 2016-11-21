<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (empty($route)) {
    $route = 'mautic_formfield_action';
}
?>

<div class="form-buttons btn-group" role="group" aria-label="Field options">
    <button type="button" data-toggle="ajaxmodal" data-target="#formComponentModal" href="<?php echo $view['router']->path($route, ['objectAction' => 'edit', 'objectId' => $id, 'formId' => $formId]); ?>" class="btn btn-default btn-edit">
        <i class="fa fa-pencil-square-o text-primary"></i>
    </button>
    <?php if (empty($disallowDelete)): ?>
    <a type="button" data-hide-panel="true" data-toggle="ajax" data-ignore-formexit="true" data-method="POST" data-hide-loadingbar="true" href="<?php echo $view['router']->path($route, ['objectAction' => 'delete', 'objectId' => $id, 'formId' => $formId]); ?>" class="btn btn-default">
        <i class="fa fa-trash-o text-danger"></i>
    </a>
    <?php endif; ?>
</div>
