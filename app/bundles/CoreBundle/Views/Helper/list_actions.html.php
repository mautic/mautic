<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$groupType = 'dropdown';
include 'action_button_helper.php';
?>
<div class="input-group input-group-sm">
    <span class="input-group-addon">
        <input type="checkbox" data-toggle="selectrow" class="list-checkbox" name="cb<?php echo $item->getId(); ?>" value="<?php echo $item->getId(); ?>" />
    </span>

    <div class="btn-group">
        <button type="button" class="btn btn-default btn-sm dropdown-toggle btn-nospin" data-toggle="dropdown">
            <i class="fa fa-angle-down "></i>
        </button>
        <ul class="pull-<?php echo $pull; ?> page-list-actions dropdown-menu" role="menu">
            <?php echo $renderPreCustomButtons($buttonCount); ?>

            <?php if (!empty($templateButtons['edit'])): ?>
            <li>
                <a href="<?php echo $view['router']->generate('mautic_' . $routeBase . '_action', array_merge(array("objectAction" => "edit", "objectId" => $item->getId()), $query)); ?>" data-toggle="<?php echo $editMode; ?>"<?php echo $editAttr.$menuLink; ?>>
                    <span><i class="fa fa-pencil-square-o"></i> <?php echo $view['translator']->trans('mautic.core.form.edit'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (!empty($templateButtons['clone'])): ?>
            <li>
                <a href="<?php echo $view['router']->generate('mautic_' . $routeBase . '_action', array_merge(array("objectAction" => "clone", "objectId" => $item->getId()), $query)); ?>" data-toggle="ajax"<?php echo $menuLink; ?>>
                    <span><i class="fa fa-copy"></i> <?php echo $view['translator']->trans('mautic.core.form.clone'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (!empty($templateButtons['delete'])): ?>
            <li>
                <?php echo $view->render('MauticCoreBundle:Helper:confirm.html.php', array(
                    'btnClass'      => false,
                    'message'       => $view["translator"]->trans("mautic." . $langVar . ".form.confirmdelete", array("%name%" => $item->$nameGetter() . " (" . $item->getId() . ")")),
                    'confirmAction' => $view['router']->generate('mautic_' . $routeBase . '_action', array_merge(array("objectAction" => "delete", "objectId" => $item->getId()), $query)),
                    'template'      => 'delete'
                )); ?>
            </li>
            <?php endif; ?>

            <?php echo $renderPostCustomButtons($buttonCount); ?>
        </ul>
    </div>
</div>
