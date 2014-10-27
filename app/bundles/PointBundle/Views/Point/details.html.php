<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'point');
$view['slots']->set("headerTitle", $entity->getName());
?>
<?php $view['slots']->start("actions"); ?>
<?php if ($permissions['point:points:edit']): ?>
        <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
            'mautic_point_action', array("objectAction" => "edit", "objectId" => $entity->getId())); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_point_index">
            <i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
        </a>
<?php endif; ?>
<?php if ($permissions['point:points:delete']): ?>
    <a class="btn btn-default" href="javascript:void(0);"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.point.confirmdelete",
           array("%name%" => $entity->getName() . " (" . $entity->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_point_action',
           array("objectAction" => "delete", "objectId" => $entity->getId())); ?>',
           '#mautic_point_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
    </a>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<div class="scrollable point-details">
    <?php //@todo - output point details/actions ?>
    @todo - output point details/actions
</div>