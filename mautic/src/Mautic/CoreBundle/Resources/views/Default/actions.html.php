<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php
    if ($edit): ?>
    <a class="btn btn-primary btn-xs"
       href="<?php echo $view['router']->generate('mautic_' . $routeBase . '_action',
           array("objectAction" => "edit", "objectId" => $item->getId())); ?>"
       data-toggle="ajax"
       <?php if (isset($menuLink)):?>
       data-menu-link="<?php echo $menuLink; ?>"
       <?php endif; ?>
       >
        <i class="fa fa-pencil-square-o"></i>
    </a>
<?php endif; ?>
<?php
    if ($delete): ?>
    <a class="btn btn-danger btn-xs" href="javascript:void(0);"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic." . $langVar . ".form.confirmdelete",
                array("%name%" => $item->getName() . " (" . $item->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_' . $routeBase . '_action',
                array("objectAction" => "delete", "objectId" => $item->getId())); ?>',
           '#<?php echo $menuLink; ?>'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <i class="fa fa-trash-o"></i>
    </a>
<?php endif; ?>