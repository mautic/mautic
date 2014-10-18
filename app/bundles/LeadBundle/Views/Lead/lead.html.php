<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\LeadBundle\Entity\Lead $lead */
/** @var array $fields */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'lead');
$view['slots']->set("headerTitle",
    '<span class="span-block">' . $view['translator']->trans($lead->getPrimaryIdentifier()) . '</span><span class="span-block small">' .
    $lead->getSecondaryIdentifier() . '</span>');
$hasEditAccess = $security->hasEntityAccess($permissions['lead:leads:editown'], $permissions['lead:leads:editother'], $lead->getOwner());

$view['slots']->append('modal', $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id' => 'leadModal'
)));

$groups = array_keys($fields);

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
    <a id="addNoteButton" class="btn btn-default" href="<?php echo $this->container->get('router')->generate('mautic_leadnote_action', array('leadId' => $lead->getId(), 'objectAction' => 'new', 'leadId' => $lead->getId())); ?>" data-toggle="ajaxmodal" data-target="#leadModal" data-header="<?php echo $view['translator']->trans('mautic.lead.note.header.new'); ?>">
       <i class="fa fa-file-o"></i>
        <?php echo $view["translator"]->trans("mautic.lead.add.note"); ?>
    </a>
<?php
endif;
$view['slots']->stop();
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- lead detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-6 va-m">
                        <div class="media">
                            <span class="pull-left img-wrapper img-rounded" style="width:38px">
                                <img src="<?php echo $view['gravatar']->getImage($fields['core']['email']['value']); ?>" alt="">
                            </span>
                            <div class="media-body">
                                <h4 class="fw-sb text-primary"><?php echo $lead->getPrimaryIdentifier(); ?></h4>
                                <p class="dark-lg mb-0"><?php echo $fields['core']['position']['value']; ?> <?php echo $fields['core']['company']['value']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-4 va-m text-right">
                        <?php
                        $color = $lead->getColor();
                        $style = !empty($color) ? ' style="background-color: ' . $color . ' !important;"' : '';
                        ?>
                        <h1 class="fw-sb dark-md<?php echo $style; ?>"><?php echo $lead->getPoints(); ?></h1>
                    </div>
                </div>
            </div>
            <!--/ lead detail header -->

            <!-- lead detail collapseable -->
            <div class="collapse" id="lead-details">

                <ul class="nav nav-tabs pr-md pl-md" role="tablist">
                <?php $step = 0; ?>
                <?php foreach ($groups as $g): ?>
                    <?php if (!empty($fields[$g])): ?>
                        <li class="<?php if ($step === 0) echo "active"; ?>">
                            <a href="#<?php echo $g; ?>" class="group" data-toggle="tab">
                                <?php echo $view['translator']->trans('mautic.lead.field.group.' . $g); ?>
                            </a>
                        </li>
                        <?php $step++; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                </ul>

                <!-- start: tab-content -->
                <div class="tab-content pa-md">
                    <?php $i = 0; ?>
                    <?php foreach ($groups as $group): ?>
                        <div class="tab-pane fade <?php echo $i == 0 ? 'in active' : ''; ?> bdr-w-0" id="<?php echo $group; ?>">
                            <div class="pr-md pl-md pb-md">
                                <div class="panel shd-none mb-0">
                                    <table class="table table-bordered table-striped mb-0">
                                        <tbody>
                                             <?php if ($group == 'core') : ?>
                                                <tr>
                                                    <td width="20%"><span class="fw-b">Company</span></td>
                                                    <td><?php echo $lead->getSecondaryIdentifier(); ?></td>
                                                </tr>
                                                <tr>
                                                    <td width="20%"><span class="fw-b">Position</span></td>
                                                    <td><?php echo $fields['core']['position']['value']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td width="20%"><span class="fw-b">Email</span></td>
                                                    <td><?php echo $fields['core']['email']['value']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td width="20%"><span class="fw-b">Phone</span></td>
                                                    <td><?php echo $fields['core']['phone']['value']; ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($fields[$group] as $field): ?>
                                                    <tr>
                                                        <td width="20%"><span class="fw-b"><?php echo $field['label']; ?></span></td>
                                                        <td><?php echo $field['value']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <!--/ lead detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- lead detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#lead-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.lead.lead.header.leadinfo'); ?></a>
                </span>
            </div>
            <!--/ lead detail collapseable toggler -->

            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-xs-8 va-m">
                                    <h5 class="dark-md fw-sb mb-xs">Engagements</h5>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="dark-sm"><span class="fa fa-eye"></span></h3>
                                </div>
                            </div>
                            <div class="plugin-sparkline text-right pr-md pl-md"
                            sparkHeight="120"
                            sparkWidth="100%"
                            sparkType="line"
                            sparkZeroAxis="false"
                            sparkBarColor="#00B49C">
                                129,137,186,167,200,350,240,220,280,264,284,400
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active"><a href="#history-container" role="tab" data-toggle="tab"><span class="label label-primary mr-sm" id="HistoryCount"><?php echo count($events); ?></span> <?php echo $view['translator']->trans('mautic.lead.lead.tab.history'); ?></a></li>
                <li class=""><a href="#notes-container" role="tab" data-toggle="tab"><span class="label label-primary mr-sm" id="NoteCount"><?php echo $noteCount; ?></span> <?php echo $view['translator']->trans('mautic.lead.lead.tab.notes'); ?></a></li>
                <li class=""><a href="#social-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.lead.lead.tab.social'); ?></a></li>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #history-container -->
            <div class="tab-pane fade in active bdr-w-0" id="history-container">
                <?php echo $view->render('MauticLeadBundle:Lead:history.html.php', array('events' => $events, 'eventTypes' => $eventTypes, 'eventFilter' => $eventFilter, 'lead' => $lead)); ?>
            </div>
            <!--/ #history-container -->

            <!-- #notes-container -->
            <div class="tab-pane fade bdr-w-0" id="notes-container">
                <?php
                //forward to Note::index controller action so that it handles pagination, etc
                echo $view['actions']->render(new \Symfony\Component\HttpKernel\Controller\ControllerReference('MauticLeadBundle:Note:index', array('leadId' => $lead->getId(), 'ignoreAjax' => 1)));
                ?>
            </div>
            <!--/ #notes-container -->

            <!-- #social-container -->
            <div class="tab-pane fade bdr-w-0" id="social-container">
                <?php echo $view->render('MauticLeadBundle:Social:index.html.php', array('socialProfiles' => $socialProfiles, 'lead' => $lead, 'socialProfileUrls' => $socialProfileUrls)); ?>
            </div>
            <!--/ #social-container -->
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- form HTML -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title">Contact</div>
            </div>
            <div class="panel-body pt-sm">
                <h6 class="fw-sb">Address</h6>
                <address class="text-muted">
                    <?php echo $fields['core']['address1']['value']; ?><br>
                    <?php if (!empty($fields['core']['address2']['value'])) : echo $fields['core']['address2']['value'] . '<br>'; endif ?>
                    <?php echo $lead->getLocation(); ?> <?php echo $fields['core']['zipcode']['value']; ?><br>
                    <abbr title="Phone">P:</abbr> <?php echo $fields['core']['phone']['value']; ?>
                </address>

                <h6 class="fw-sb">Email</h6>
                <p class="text-muted"><?php echo $fields['core']['email']['value']; ?></p>

                <h6 class="fw-sb">Phone - home</h6>
                <p class="text-muted"><?php echo $fields['core']['phone']['value']; ?></p>

                <h6 class="fw-sb">Phone - mobile</h6>
                <p class="text-muted mb-0"><?php echo $fields['core']['mobile']['value']; ?></p>
            </div>
        </div>
        <!--/ form HTML -->

        <hr class="hr-w-2" style="width:50%">

        <!--
        we can leverage data from audit_log table
        and build activity feed from it
        -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mb-0">
            <div class="panel-heading">
                <div class="panel-title">Recent Activity</div>
            </div>
            <div class="panel-body pt-sm">
                <ul class="media-list media-list-feed">
                    <li class="media">
                        <div class="media-object pull-left mt-xs">
                            <span class="figure"></span>
                        </div>
                        <div class="media-body">
                            Dan Counsell Create <strong class="text-primary">Super Awesome Campaign</strong>
                            <p class="fs-12 text-white dark-sm">Jan 16, 2014</p>
                        </div>
                    </li>
                    <li class="media">
                        <div class="media-object pull-left mt-xs">
                            <span class="figure"></span>
                        </div>
                        <div class="media-body">
                            Ima Steward Update <strong class="text-primary">Super Awesome Campaign</strong> action
                            <p class="fs-12 text-white dark-sm">May 1, 2015</p>
                        </div>
                    </li>
                    <li class="media">
                        <div class="media-object pull-left mt-xs">
                            <span class="figure"></span>
                        </div>
                        <div class="media-body">
                            Ima Steward Update <strong class="text-primary">Super Awesome Campaign</strong> leads
                            <p class="fs-12 text-white dark-sm">Aug 2, 2014</p>
                        </div>
                    </li>
                    <li class="media">
                        <div class="media-object pull-left">
                            <span class="figure featured bg-success"><span class="fa fa-check"></span></span>
                        </div>
                        <div class="media-body">
                            Dan Counsell Publish <strong class="text-primary">Super Awesome Campaign</strong>
                            <p class="fs-12 text-white dark-sm">Sep 23, 2014</p>
                        </div>
                    </li>
                    <li class="media">
                        <div class="media-object pull-left">
                            <span class="figure"></span>
                        </div>
                        <div class="media-body">
                            Dan Counsell Unpublish <strong class="text-primary">Super Awesome Campaign</strong>
                            <p class="fs-12 text-white dark-sm">Sep 29, 2014</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->