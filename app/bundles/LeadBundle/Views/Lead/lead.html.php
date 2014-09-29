<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'lead');
$view['slots']->set("headerTitle",
    '<span class="span-block">' . $view['translator']->trans($lead->getPrimaryIdentifier())) . '</span><span class="span-block small">' .
    $lead->getSecondaryIdentifier() . '</span>';
$hasEditAccess = $security->hasEntityAccess($permissions['lead:leads:editown'], $permissions['lead:leads:editother'], $lead->getOwner());

$view['slots']->start('modal');
echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id' => 'leadModal'
));
$view['slots']->stop();

$view['slots']->start("actions");
if ($hasEditAccess): ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
        'mautic_lead_action', array("objectAction" => "edit", "objectId" => $lead->getId())); ?>"
       data-toggle="ajax"
       data-menu-link="#mautic_lead_index">
       <i class="fa fa-pencil-square-o"></i>
        <?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
    </a>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['lead:leads:deleteown'], $permissions['lead:leads:deleteother'], $lead->getOwner())): ?>
    <a class="btn btn-default" href="javascript:void(0);"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.lead.lead.form.confirmdelete",
           array("%name%" => $lead->getPrimaryIdentifier() . " (" . $lead->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_lead_action',
           array("objectAction" => "delete", "objectId" => $lead->getId())); ?>',
           '#mautic_lead_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <i class="fa fa-trash text-danger"></i>
        <span><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
    </a>
<?php endif; ?>
<?php if ($hasEditAccess): ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate( 'mautic_lead_action', array(
        "objectId" => $lead->getId(),
        "objectAction" => "list"
    )); ?>"
       data-toggle="ajaxmodal"
       data-target="#leadModal"
       data-header="<?php echo $view['translator']->trans('mautic.lead.lead.header.lists', array(
               '%name%' => $lead->getPrimaryIdentifier())
       ); ?>">
       <i class="fa fa-list"></i>
        <?php echo $view["translator"]->trans("mautic.lead.lead.lists"); ?>
    </a>
    <?php if ($security->isGranted('campaign:campaigns:edit')): ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate( 'mautic_lead_action', array(
        "objectId" => $lead->getId(),
        "objectAction" => "campaign"
    )); ?>"
       data-toggle="ajaxmodal"
       data-target="#leadModal"
       data-header="<?php echo $view['translator']->trans('mautic.lead.lead.header.campaigns', array(
               '%name%' => $lead->getPrimaryIdentifier())
       ); ?>">
        <i class="fa fa-clock-o"></i>
        <?php echo $view["translator"]->trans("mautic.lead.lead.campaigns"); ?>
    </a>
    <?php endif; ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
        'mautic_leadnote_action', array("leadId" => $lead->getId(), "objectAction" => "new")); ?>"
       data-toggle="ajaxmodal"
       data-target="#leadModal"
       data-header="<?php $view['translator']->trans('mautic.lead.note.header.new'); ?>">
       <i class="fa fa-file-o"></i>
        <?php echo $view["translator"]->trans("mautic.lead.add.note"); ?>
    </a>
<?php
endif;
$view['slots']->stop();
?>
<div class="scrollable lead-details">
  <div class="row">
    <div class="col-sm-3">
      <?php
      echo $view->render('MauticLeadBundle:Lead:info.html.php', array(
          "lead"              => $lead,
          'fields'            => $fields,
          'socialProfileUrls' => $socialProfileUrls
      ));
      ?>
    </div>
    <div class="col-md-9">
      <ul class="timeline">
        <li class="header year ellipsis">Recent Events</li>
        <li class="wrapper">
          <ul class="events">
            <?php foreach ($events as $event) : ?>
              <li class="wrapper<?php if ($event['event'] == 'lead.created') echo ' featured'; ?>">
                <div class="figure bgcolor-success"><i class="ico-file-plus"></i></div>
                <div class="panel">
                  <div class="panel-body">
                    <ul class="list-table">
                      <!-- <li class="text-left" style="width:60px;">
                        <img class="img-circle" src="../image/avatar/avatar9.jpg" alt="" width="50px" height="50px">
                      </li> -->
                      <li class="text-left">
                        <p class="mb5">At <?php echo $view['date']->toFullConcat($event['timestamp']); ?>, <?php echo $event['event']; ?>.</p>
                      </li>
                    </ul>
                  </div>
                  <?php if (isset($event['extra'])) : ?>
                    <div class="panel-footer">
                      <?php print_r($event['extra']); ?>
                    </div>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</div>
