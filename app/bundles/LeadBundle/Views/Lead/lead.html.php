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

<!-- reset container-fluid padding -->
<div class="mna-md">
    <!-- start: box layout -->
    <div class="box-layout">
        <!-- left section -->
        <div class="col-md-9 bg-white height-auto">
            <div class="bg-auto">
                <!-- header -->
                <div class="bg-picture mb-lg mnt-1" style="background-image:url('http://www.graphicsfuel.com/wp-content/uploads/2014/09/polygon-background3-preview.jpg')">
                    <!-- overlay -->
                    <span class="bg-picture-overlay"></span>
                    
                    <!-- meta bottom -->
                    <div class="box-layout meta bottom pa-md">
                        <div class="col-sm-8 va-m">
                            <div class="media-body">
                                <h3 class="text-white mb-2 fw-sb ellipsis">Leads Name Or Title</h3>
                                <h5 class="text-white dark-xs">Company Name PTE</h5>
                            </div>
                        </div>
                        <div class="col-sm-4 va-m text-right">
                            <h2 class="fw-sb text-white"><span class="fa fa-star-o text-warning"></span> 105</h2>
                            <h5 class="text-white dark-xs">Lead Points</h5>
                        </div>
                    </div>
                    <!--/ meta bottom -->
                </div>
                <!--/ header -->

                <!-- tabs controls -->
                <ul class="nav nav-tabs pr-md pl-md">
                    <li class="active"><a href="#history-container" role="tab" data-toggle="tab"><span class="label label-primary mr-sm">12</span> History</a></li>
                    <li class=""><a href="#notes-container" role="tab" data-toggle="tab">Notes</a></li>
                    <li class=""><a href="#social-container" role="tab" data-toggle="tab">Social</a></li>
                </ul>
                <!--/ tabs controls -->
            </div>

            <!-- start: tab-content -->
            <div class="tab-content pa-md">
                <!-- #history-container -->
                <div class="tab-pane fade in active bdr-w-0" id="history-container">
                    <!-- form -->
                    <form action="" class="panel">
                        <div class="form-control-icon pa-xs">
                            <input type="text" class="form-control bdr-w-0" placeholder="Search...">
                            <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                        </div>
                    </form>
                    <!--/ form -->

                    <!-- timeline -->
                    <ul class="timeline">
                        <li class="header ellipsis bg-white">Recent Events</li>
                        <li class="wrapper">
                            <ul class="events">
                                <?php foreach ($events as $event) : ?>
                                <li class="<?php if ($event['event'] == 'lead.created') echo 'featured'; else echo 'wrapper'; ?>">
                                    <div class="figure"><!--<span class="fa fa-check"></span>--></div>
                                    <div class="panel <?php if ($event['event'] == 'lead.created') echo 'bg-primary'; ?>">
                                        <div class="panel-body">
                                            <p class="mb-0">At <?php echo $view['date']->toFullConcat($event['timestamp']); ?>, <?php echo $event['event']; ?>.</p>
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
                    <!--/ timeline -->
                </div>
                <!--/ #history-container -->

                <!-- #notes-container -->
                <div class="tab-pane fade bdr-w-0" id="notes-container">
                    
                    <!-- form -->
                    <form action="" class="panel">
                        <div class="form-control-icon pa-xs">
                            <input type="text" class="form-control bdr-w-0" placeholder="Search...">
                            <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                        </div>
                    </form>
                    <!--/ form -->

                    Excepteur sint occaecat cupidatat non
                    proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                    tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
                    quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                    consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                    cillum dolore eu fugiat nulla pariatur.
                </div>
                <!--/ #notes-container -->

                <!-- #social-container -->
                <div class="tab-pane fade bdr-w-0" id="social-container">
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                    tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
                    quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                    consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                    cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
                    proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                </div>
                <!--/ #social-container -->
            </div>
            <!--/ end: tab-content -->
        </div>
        <!--/ left section -->

        <!-- right section -->
        <div class="col-md-3 bg-auto bdr-l height-auto">
            <!-- profile -->
            <div class="panel bdr-rds-0 bdr-w-0 shd-none bg-transparent mb-0">
                <div class="panel-body text-center">
                    <span class="img-wrapper img-rounded mb-md mt-lg" style="width:76px;">
                        <img src="https://s3.amazonaws.com/uifaces/faces/twitter/nisaanjani/128.jpg" alt="">
                    </span>
                    <h4 class="fw-sb mb-xs">Nisa Anjani</h4>
                    <p class="mb-0 text-muted"><span class="fa fa-map-marker"></span> Melbourne, Australia</p>

                    <div class="mt-lg mb-lg">
                        <a href="javascript:void(0);" class="text-twitter"><span class="fa fa-twitter fs-18 mr-md"></span></a>
                        <a href="javascript:void(0);" class="text-facebook"><span class="fa fa-facebook fs-18 mr-md"></span></a>
                        <a href="javascript:void(0);" class="text-google"><span class="fa fa-google-plus fs-18"></span></a>
                    </div>
                    <a href="" class="btn btn-default"><span class="fa fa-envelope mr-xs"></span> Message</a>
                </div>
            </div>
            <!--/ profile -->

            <div class="pa-md">
                <!-- about -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="panel-title">General</div>
                    </div>
                    <div class="panel-body">
                        <h6 class="fw-sb">Position</h6>
                        <p class="text-muted">Senior CEO</p>

                        <h6 class="fw-sb">Company</h6>
                        <p class="text-muted mb-0">Lorem Ipsum LTD</p>
                    </div>
                </div>
                <!--/ about -->

                <!-- about -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="panel-title">Contact</div>
                    </div>
                    <div class="panel-body">
                        <h6 class="fw-sb">Address</h6>
                        <address class="text-muted">
                            795 Folsom Ave, Suite 600<br>
                            San Francisco, CA 94107<br>
                            <abbr title="Phone">P:</abbr> (123) 456-7890
                        </address>

                        <h6 class="fw-sb">Email</h6>
                        <p class="text-muted">nisa.anjani@mail.com</p>

                        <h6 class="fw-sb">Phone - home</h6>
                        <p class="text-muted">(222) 222-2222</p>

                        <h6 class="fw-sb">Phone - mobile</h6>
                        <p class="text-muted mb-0">(333) 333-3333</p>
                    </div>
                </div>
                <!--/ about -->
            </div>
        </div>
        <!--/ right section -->
    </div>
    <!--/ end: box layout -->
</div>
<!--/ reset container-fluid padding -->