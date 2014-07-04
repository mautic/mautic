<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$nameGetter = (!empty($nameGetter)) ? $nameGetter : 'getName';
?>
<div class="btn-group">
    <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
        <i class="fa fa-angle-down"></i>
    </button>
    <ul class="bundle-list-actions dropdown-menu" role="menu">
        <?php if (!empty($edit)): ?>
        <li>
            <a href="<?php echo $view['router']->generate('mautic_' . $routeBase . '_action',
                   array("objectAction" => "edit", "objectId" => $item->getId())); ?>"
               data-toggle="ajax"
               <?php if (isset($menuLink)):?>
               data-menu-link="<?php echo $menuLink; ?>"
               <?php endif; ?>
               >
                <span><i class="fa fa-pencil-square-o"></i><?php echo $view['translator']->trans('mautic.core.form.edit'); ?></span>
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
                        array("objectAction" => "delete", "objectId" => $item->getId())); ?>',
                   '#<?php echo $menuLink; ?>'],
                   '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
                <span><i class="fa fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>