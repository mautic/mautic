<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$nameGetter = (!empty($nameGetter)) ? $nameGetter : 'getName';
if (empty($pull)) {
    $pull = 'left';
}
if (!isset($extra)) {
    $extra = array();
}

?>
<div class="btn-group">
    <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
        <i class="fa fa-angle-down"></i>
    </button>
    <ul class="pull-<?php echo $pull; ?> page-list-actions dropdown-menu" role="menu">
        <?php if (!empty($edit)): ?>
            <li>
                <a href="<?php echo $view['router']->generate('mautic_' . $routeBase . '_action',
                    array_merge(array("objectAction" => "edit", "objectId" => $item->getId()), $extra)); ?>"
                   data-toggle="ajax"
                    <?php if (isset($menuLink)):?>
                    data-menu-link="<?php echo $menuLink; ?>"
                    <?php endif; ?>>
                    <span><i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view['translator']->trans('mautic.core.form.edit'); ?></span>
                </a>
            </li>
        <?php
        endif;
        if (!empty($clone)): ?>
        <li>
            <a href="<?php echo $view['router']->generate('mautic_' . $routeBase . '_action',
                   array_merge(array("objectAction" => "clone", "objectId" => $item->getId()), $extra)); ?>"
               data-toggle="ajax"
                <?php if (isset($menuLink)):?>
                data-menu-link="<?php echo $menuLink; ?>"
                <?php endif; ?>>
                <span><i class="fa fa-fw fa-copy"></i><?php echo $view['translator']->trans('mautic.core.form.clone'); ?></span>
            </a>
        </li>
        <?php
        endif;
        if (!empty($delete)): ?>
        <li>
            <a href="javascript:void(0);"
               onclick="Mautic.showConfirmation(
                   '<?php echo $view->escape($view["translator"]->trans("mautic." . $langVar . ".form.confirmdelete",
                        array("%name%" => $item->$nameGetter() . " (" . $item->getId() . ")")), 'js'); ?>',
                   '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
                   'executeAction',
                   ['<?php echo $view['router']->generate('mautic_' . $routeBase . '_action',
                        array_merge(array("objectAction" => "delete", "objectId" => $item->getId()), $extra)); ?>',
                   '#<?php echo $menuLink; ?>'],
                   '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
                <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (!empty($custom)): ?>
        <?php echo $custom; ?>
        <?php endif; ?>
    </ul>
</div>