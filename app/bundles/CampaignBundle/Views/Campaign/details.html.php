<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'campaign');
$view['slots']->set("headerTitle", $entity->getName());
?>
<?php $view['slots']->start("actions"); ?>
<?php if ($permissions['campaign:campaigns:edit']): ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_campaign_action', array("objectAction" => "edit", "objectId" => $entity->getId())); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_campaign_index">
            <i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
        </a>
    </li>
<?php endif; ?>
<?php if ($permissions['campaign:campaigns:delete']): ?>
<li>
    <a href="javascript:void(0);"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.campaign.confirmdelete",
           array("%name%" => $entity->getName() . " (" . $entity->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_campaign_action',
           array("objectAction" => "delete", "objectId" => $entity->getId())); ?>',
           '#mautic_campaign_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
    </a>
</li>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<div class="scrollable campaign-details">
    <?php //@todo - output campaign details/actions ?>
    @todo - output campaign details/actions
</div>