<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'pointRange');
$view['slots']->set("headerTitle", $entity->getName());
?>
<?php $view['slots']->start("actions"); ?>
<?php if ($security->hasEntityAccess($permissions['point:ranges:editown'], $permissions['point:ranges:editother'],
    $entity->getCreatedBy())): ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_pointrange_action', array("objectAction" => "edit", "objectId" => $entity->getId())); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_pointrange_index">
            <i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
        </a>
    </li>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['point:ranges:deleteown'], $permissions['point:ranges:deleteother'],
    $entity->getCreatedBy())): ?>
<li>
    <a href="javascript:void(0);"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.point.range.confirmdelete",
           array("%name%" => $entity->getName() . " (" . $entity->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_pointrange_action',
           array("objectAction" => "delete", "objectId" => $entity->getId())); ?>',
           '#mautic_pointrange_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
    </a>
</li>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<div class="scrollable point-details">
    <?php //@todo - output range details/actions ?>
    @todo - output range details/actions
</div>