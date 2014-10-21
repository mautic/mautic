<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'pointTrigger');
$view['slots']->set("headerTitle", $entity->getName());
?>
<?php $view['slots']->start("actions"); ?>
<?php if ($permissions['point:triggers:edit']): ?>
  <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
      'mautic_pointtrigger_action', array("objectAction" => "edit", "objectId" => $entity->getId())); ?>"
     data-toggle="ajax"
     data-menu-link="#mautic_pointtrigger_index">
      <i class="fa fa-fw fa-pencil-square-o"></i> <?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
  </a>
<?php endif; ?>
<?php if ($permissions['point:triggers:delete']): ?>
  <a href="javascript:void(0);"
     class="btn btn-default" onclick="Mautic.showConfirmation(
         '<?php echo $view->escape($view["translator"]->trans("mautic.point.trigger.confirmdelete",
         array("%name%" => $entity->getName() . " (" . $entity->getId() . ")")), 'js'); ?>',
         '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
         'executeAction',
         ['<?php echo $view['router']->generate('mautic_pointtrigger_action',
         array("objectAction" => "delete", "objectId" => $entity->getId())); ?>',
         '#mautic_pointtrigger_index'],
         '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <i class="fa fa-fw fa-trash-o"></i> <?php echo $view['translator']->trans('mautic.core.form.delete'); ?>
  </a>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<div class="scrollable trigger-details">
    <?php //@todo - output trigger details/actions ?>
    @todo - output trigger details/actions
</div>