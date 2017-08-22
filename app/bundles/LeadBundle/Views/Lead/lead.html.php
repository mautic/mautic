<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\LeadBundle\Entity\Lead $lead */
/** @var array $fields */
$view->extend('MauticCoreBundle:Default:content.html.php');

$isAnonymous = $lead->isAnonymous();

$flag = (!empty($fields['core']['country'])) ? $view['assets']->getCountryFlag($fields['core']['country']['value']) : '';

$leadName       = ($isAnonymous) ? $view['translator']->trans($lead->getPrimaryIdentifier()) : $lead->getPrimaryIdentifier();
$leadActualName = $lead->getName();
$leadCompany    = $lead->getCompany();

$view['slots']->set('mauticContent', 'lead');

$avatar = '';
if (!$isAnonymous) {
    $img    = $view['lead_avatar']->getAvatar($lead);
    $avatar = '<span class="pull-left img-wrapper img-rounded mr-10" style="width:33px"><img src="'.$img.'" alt="" /></span>';
}

$view['slots']->set(
    'headerTitle',
    $avatar.'<div class="pull-left mt-5"><span class="span-block">'.$leadName.'</span><span class="span-block small ml-sm">'
    .$lead->getSecondaryIdentifier().'</span></div>'
);

$groups = array_keys($fields);
$edit   = $view['security']->hasEntityAccess(
    $permissions['lead:leads:editown'],
    $permissions['lead:leads:editother'],
    $lead->getPermissionUser()
);

$buttons = [];
//Send email button
if (!empty($fields['core']['email']['value'])) {
    $buttons[] = [
        'attr' => [
            'id'          => 'sendEmailButton',
            'data-toggle' => 'ajaxmodal',
            'data-target' => '#MauticSharedModal',
            'data-header' => $view['translator']->trans(
                'mautic.lead.email.send_email.header',
                ['%email%' => $fields['core']['email']['value']]
            ),
            'href' => $view['router']->path(
                'mautic_contact_action',
                ['objectId' => $lead->getId(), 'objectAction' => 'email']
            ),
        ],
        'btnText'   => $view['translator']->trans('mautic.lead.email.send_email'),
        'iconClass' => 'fa fa-send',
        'primary'   => true,
    ];
}

//View Contact Frequency button

if ($edit) {
    $buttons[] = [
        'attr' => [
            'data-toggle' => 'ajaxmodal',
            'data-target' => '#MauticSharedModal',
            'data-header' => $view['translator']->trans(
                'mautic.lead.lead.header.contact.frequency',
                ['%name%' => $lead->getPrimaryIdentifier()]
            ),
            'href' => $view['router']->path(
                'mautic_contact_action',
                ['objectId' => $lead->getId(), 'objectAction' => 'contactFrequency']
            ),
        ],
        'btnText'   => $view['translator']->trans('mautic.lead.contact.frequency'),
        'iconClass' => 'fa fa-signal',
    ];
}
//View Campaigns List button
if ($view['security']->isGranted('campaign:campaigns:edit')) {
    $buttons[] = [
        'attr' => [
            'data-toggle' => 'ajaxmodal',
            'data-target' => '#MauticSharedModal',
            'data-header' => $view['translator']->trans(
                'mautic.lead.lead.header.campaigns',
                ['%name%' => $lead->getPrimaryIdentifier()]
            ),
            'data-footer' => 'false',
            'href'        => $view['router']->path(
                'mautic_contact_action',
                ['objectId' => $lead->getId(), 'objectAction' => 'campaign']
            ),
        ],
        'btnText'   => $view['translator']->trans('mautic.campaign.campaigns'),
        'iconClass' => 'fa fa-clock-o',
    ];
}
//Merge button
if (($view['security']->hasEntityAccess(
        $permissions['lead:leads:deleteown'],
        $permissions['lead:leads:deleteother'],
        $lead->getPermissionUser()
    ))
    && $edit
) {
    $buttons[] = [
        'attr' => [
            'data-toggle' => 'ajaxmodal',
            'data-target' => '#MauticSharedModal',
            'data-header' => $view['translator']->trans(
                'mautic.lead.lead.header.merge',
                ['%name%' => $lead->getPrimaryIdentifier()]
            ),
            'href' => $view['router']->path(
                'mautic_contact_action',
                ['objectId' => $lead->getId(), 'objectAction' => 'merge']
            ),
        ],
        'btnText'   => $view['translator']->trans('mautic.lead.merge'),
        'iconClass' => 'fa fa-user',
    ];
}

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $lead,
            'routeBase'       => 'contact',
            'langVar'         => 'lead.lead',
            'customButtons'   => $buttons,
            'templateButtons' => [
                'edit'   => $edit,
                'delete' => $view['security']->hasEntityAccess(
                    $permissions['lead:leads:deleteown'],
                    $permissions['lead:leads:deleteother'],
                    $lead->getPermissionUser()
                ),
                'close' => $view['security']->hasEntityAccess(
                    $permissions['lead:leads:viewown'],
                    $permissions['lead:leads:viewother'],
                    $lead->getPermissionUser()
                ),
            ],
        ]
    )
);
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!--/ lead detail header -->

            <!-- lead detail collapseable -->
            <div class="collapse" id="lead-details">
                <ul class="pt-md nav nav-tabs pr-md pl-md" role="tablist">
                    <?php $step = 0; ?>
                    <?php foreach ($groups as $g): ?>
                        <?php if (!empty($fields[$g])): ?>
                            <li class="<?php if ($step === 0) {
    echo 'active';
} ?>">
                                <a href="#<?php echo $g; ?>" class="group" data-toggle="tab">
                                    <?php echo $view['translator']->trans('mautic.lead.field.group.'.$g); ?>
                                </a>
                            </li>
                            <?php ++$step; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>

                <!-- start: tab-content -->
                <div class="tab-content pa-md bg-white">
                    <?php $i = 0; ?>
                    <?php foreach ($groups as $group): ?>
                        <div class="tab-pane fade <?php echo $i == 0 ? 'in active' : ''; ?> bdr-w-0"
                             id="<?php echo $group; ?>">
                            <div class="pr-md pl-md pb-md">
                                <div class="panel shd-none mb-0">
                                    <table class="table table-bordered table-striped mb-0">
                                        <tbody>
                                        <?php foreach ($fields[$group] as $field): ?>
                                            <tr>
                                                <td width="20%"><span class="fw-b"><?php echo $field['label']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($group == 'core' && $field['alias'] == 'country' && !empty($flag)): ?>
                                                    <img class="mr-sm" src="<?php echo $flag; ?>" alt="" style="max-height: 24px;"/>
                                                    <span class="mt-1"><?php echo $field['value']; ?>
                                                    <?php else: ?>
                                                        <?php if (is_array($field['value']) && 'multiselect' === $field['type']): ?>
                                                            <?php echo implode(', ', $field['value']); ?>
                                                        <?php else: ?>
                                                            <?php echo $field['value']; ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php ++$i; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <!--/ lead detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- lead detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.details'); ?>">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#lead-details"><span class="caret"></span> <?php echo $view['translator']->trans(
                            'mautic.core.details'
                        ); ?></a>
                </span>
            </div>
            <!--/ lead detail collapseable toggler -->

            <?php if (!$isAnonymous): ?>
                <div class="pa-md">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="panel">
                                <div class="panel-body box-layout">
                                    <div class="col-xs-8 va-m">
                                        <h5 class="text-white dark-md fw-sb mb-xs">
                                            <?php echo $view['translator']->trans('mautic.lead.field.header.engagements'); ?>
                                        </h5>
                                    </div>
                                    <div class="col-xs-4 va-t text-right">
                                        <h3 class="text-white dark-sm"><span class="fa fa-eye"></span></h3>
                                    </div>
                                </div>
                                <?php echo $view->render(
                                    'MauticCoreBundle:Helper:chart.html.php',
                                    ['chartData' => $engagementData, 'chartType' => 'line', 'chartHeight' => 250]
                                ); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md mt-10">
                <li class="active">
                    <a href="#timeline-container" role="tab" data-toggle="tab">
                        <span class="label label-primary mr-sm" id="TimelineCount">
                            <?php echo $events['total']; ?>
                        </span>
                        <?php echo $view['translator']->trans('mautic.lead.lead.tab.history'); ?>
                    </a>
                </li>
                <li class="">
                    <a href="#notes-container" role="tab" data-toggle="tab">
                        <span class="label label-primary mr-sm" id="NoteCount">
                            <?php echo $noteCount; ?>
                        </span>
                        <?php echo $view['translator']->trans('mautic.lead.lead.tab.notes'); ?>
                    </a>
                </li>
                <?php if (!$isAnonymous): ?>
                    <li class="">
                        <a href="#social-container" role="tab" data-toggle="tab">
                        <span class="label label-primary mr-sm" id="SocialCount">
                            <?php echo count($socialProfiles); ?>
                        </span>
                            <?php echo $view['translator']->trans('mautic.lead.lead.tab.social'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="">
<<<<<<< HEAD
                    <a href="#integration-container" role="tab" data-toggle="tab">
                    <span class="label label-primary mr-sm" id="IntegrationCount">
                        <?php echo count($integrations); ?>
                    </span>
                        <?php echo $view['translator']->trans('mautic.lead.lead.tab.integration'); ?>
=======
                    <a href="#auditlog-container" role="tab" data-toggle="tab">
                    <span class="label label-primary mr-sm" id="AuditLogCount">
                        <?php echo $auditlog['total']; ?>
                    </span>
                        <?php echo $view['translator']->trans('mautic.lead.lead.tab.auditlog'); ?>
>>>>>>> upstream/staging
                    </a>
                </li>
                <?php if ($places): ?>
                    <li class="">
                        <a href="#place-container" role="tab" data-toggle="tab" id="load-lead-map">
                        <span class="label label-primary mr-sm" id="PlaceCount">
                            <?php echo count($places); ?>
                        </span>
                            <?php echo $view['translator']->trans('mautic.lead.lead.tab.places'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php echo $view['content']->getCustomContent('tabs', $mauticTemplateVars); ?>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #history-container -->
            <div class="tab-pane fade in active bdr-w-0" id="timeline-container">
                <?php echo $view->render(
                    'MauticLeadBundle:Timeline:list.html.php',
                    [
                        'events' => $events,
                        'lead'   => $lead,
                        'tmpl'   => 'index',
                    ]
                ); ?>
            </div>
            <!--/ #history-container -->

            <!-- #notes-container -->
            <div class="tab-pane fade bdr-w-0" id="notes-container">
                <?php echo $leadNotes; ?>
            </div>
            <!--/ #notes-container -->

            <!-- #social-container -->
            <?php if (!$isAnonymous): ?>
                <div class="tab-pane fade bdr-w-0" id="social-container">
                    <?php echo $view->render(
                        'MauticLeadBundle:Social:index.html.php',
                        [
                            'lead'              => $lead,
                            'socialProfiles'    => $socialProfiles,
                            'socialProfileUrls' => $socialProfileUrls,
                        ]
                    ); ?>
                </div>
            <?php endif; ?>
            <!--/ #social-container -->

            <!-- #integration-container -->
            <div class="tab-pane fade bdr-w-0" id="integration-container">
                <?php echo $view->render(
                    'MauticLeadBundle:Integration:index.html.php',
                    [
                        'lead'         => $lead,
                        'integrations' => $integrations,
                    ]
                ); ?>
            </div>
            <!--/ #integration-container -->

            <!-- #auditlog-container -->
            <div class="tab-pane fade bdr-w-0" id="auditlog-container">
                <?php echo $view->render(
                    'MauticLeadBundle:Auditlog:list.html.php',
                    [
                        'events' => $auditlog,
                        'lead'   => $lead,
                        'tmpl'   => 'index',
                    ]
                ); ?>
            </div>
            <!--/ #auditlog-container -->

            <!-- custom content -->
            <?php echo $view['content']->getCustomContent('tabs.content', $mauticTemplateVars); ?>
            <!-- end: custom content -->
            
            <!-- #place-container -->
            <?php if ($places): ?>
                <div class="tab-pane fade bdr-w-0" id="place-container">
                    <?php echo $view->render('MauticLeadBundle:Lead:map.html.php', ['places' => $places]); ?>
                </div>
            <?php endif; ?>
            <!--/ #place-container -->
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- form HTML -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mb-0">
            <?php if (!$lead->isAnonymous()): ?>
                <div class="lead-avatar-panel">
                    <div class="avatar-collapser hr-expand nm">
                        <a href="javascript:void(0)"
                           class="arrow text-muted text-center<?php echo ($avatarPanelState == 'expanded') ? ''
                               : ' collapsed'; ?>" data-toggle="collapse" data-target="#lead-avatar-block"><span
                                class="caret"></span></a>
                    </div>
                    <div class="collapse<?php echo ($avatarPanelState == 'expanded') ? ' in' : ''; ?>"
                         id="lead-avatar-block">
                        <img class="img-responsive" src="<?php echo $img; ?>" alt="<?php echo $leadName; ?> "/>
                    </div>
                </div>

            <?php endif; ?>
            <div class="mt-sm points-panel text-center">
                <?php
                $color = $lead->getColor();
                $style = !empty($color) ? ' style="font-color: '.$color.' !important;"' : '';
                ?>
                <h1 <?php echo $style; ?>>
                    <?php echo $view['translator']->transChoice(
                        'mautic.lead.points.count',
                        $lead->getPoints(),
                        ['%points%' => $lead->getPoints()]
                    ); ?>
                </h1>
                <hr/>
                <?php if ($lead->getStage()): ?>
                    <?php echo $lead->getStage()->getName(); ?>
                    <hr>
                <?php endif; ?>
            </div>
            <?php if ($doNotContact) : ?>
                <div id="bounceLabel<?php echo $doNotContact['id']; ?>">
                    <div class="panel-heading text-center">
                        <h4 class="fw-sb">
                            <?php if ($doNotContact['unsubscribed']): ?>
                                <span class="label label-danger" data-toggle="tooltip" title="<?php echo $doNotContact['comments']; ?>">
                                <?php echo $view['translator']->trans('mautic.lead.do.not.contact'); ?>
                            </span>

                            <?php elseif ($doNotContact['manual']): ?>
                                <span class="label label-danger" data-toggle="tooltip" title="<?php echo $doNotContact['comments']; ?>">
                                <?php echo $view['translator']->trans('mautic.lead.do.not.contact'); ?>
                                    <span data-toggle="tooltip" data-placement="bottom" title="<?php echo $view['translator']->trans(
                                        'mautic.lead.remove_dnc_status'
                                    ); ?>">
                                    <i class="fa fa-times has-click-event" onclick="Mautic.removeBounceStatus(this, <?php echo $doNotContact['id']; ?>);"></i>
                                </span>
                            </span>

                            <?php elseif ($doNotContact['bounced']): ?>
                                <span class="label label-warning" data-toggle="tooltip" title="<?php echo $doNotContact['comments']; ?>">
                                <?php echo $view['translator']->trans('mautic.lead.do.not.contact_bounced'); ?>
                                    <span data-toggle="tooltip" data-placement="bottom" title="<?php echo $view['translator']->trans(
                                        'mautic.lead.remove_dnc_status'
                                    ); ?>">
                                    <i class="fa fa-times has-click-event" onclick="Mautic.removeBounceStatus(this, <?php echo $doNotContact['id']; ?>);"></i>
                                </span>
                            </span>
                            <?php endif; ?>
                        </h4>
                    </div>
                    <hr/>
                </div>
            <?php endif; ?>
            <div class="panel-heading">
                <div class="panel-title">
                    <?php echo $view['translator']->trans('mautic.lead.field.header.contact'); ?>
                </div>
            </div>
            <div class="panel-body pt-sm">
            <?php if ($lead->getOwner()) : ?>
                <h6 class="fw-sb"><?php echo $view['translator']->trans('mautic.lead.lead.field.owner'); ?></h6>
                <p class="text-muted"><?php echo $lead->getOwner()->getName(); ?></p>
            <?php endif; ?>

                <h6 class="fw-sb">
                    <?php echo $view['translator']->trans('mautic.lead.field.address'); ?>
                </h6>
                <address class="text-muted">
                    <?php if (isset($fields['core']['address1'])): ?>
                        <?php echo $fields['core']['address1']['value']; ?><br>
                    <?php endif; ?>
                    <?php if (!empty($fields['core']['address2']['value'])) : echo $fields['core']['address2']['value']
                        .'<br>'; endif ?>
                    <?php echo $lead->getLocation(); ?> <?php if (isset($fields['core']['zipcode'])) {
                            echo $fields['core']['zipcode']['value'];
                        } ?><br>
                </address>

                <h6 class="fw-sb"><?php echo $view['translator']->trans('mautic.core.type.email'); ?></h6>
                <p class="text-muted"><?php echo $fields['core']['email']['value']; ?></p>

                <?php if (isset($fields['core']['phone'])): ?>
                    <h6 class="fw-sb"><?php echo $view['translator']->trans('mautic.lead.field.type.tel.home'); ?></h6>
                    <p class="text-muted"><?php echo $fields['core']['phone']['value']; ?></p>
                <?php endif; ?>

                <?php if (isset($fields['core']['mobile'])): ?>
                    <h6 class="fw-sb"><?php echo $view['translator']->trans('mautic.lead.field.type.tel.mobile'); ?></h6>
                    <p class="text-muted mb-0"><?php echo $fields['core']['mobile']['value']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        <!--/ form HTML -->

        <?php if ($upcomingEvents) : ?>
            <hr class="hr-w-2" style="width:50%">

            <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0">
                <div class="panel-heading">
                    <div class="panel-title"><?php echo $view['translator']->trans('mautic.lead.lead.upcoming.events'); ?></div>
                </div>
                <div class="panel-body pt-sm">
                    <ul class="media-list media-list-feed">
                        <?php foreach ($upcomingEvents as $event) : ?>
                        <?php
                            $metadata = unserialize($event['metadata']);
                            $errors   = false;
                            if (!empty($metadata['errors'])):
                                $errors = (is_array($metadata['errors'])) ? implode('<br />', $metadata['errors']) : $metadata['errors'];
                            endif;
                        ?>
                            <li class="media">
                                <div class="media-object pull-left mt-xs">
                                    <span class="figure"></span>
                                </div>
                                <div class="media-body">
                                    <?php $link = '<a href="'.$view['router']->path(
                                            'mautic_campaign_action',
                                            ['objectAction' => 'view', 'objectId' => $event['campaign_id']]
                                        ).'" data-toggle="ajax">'.$event['campaign_name'].'</a>'; ?>
                                    <?php echo $view['translator']->trans(
                                        'mautic.lead.lead.upcoming.event.triggered.at',
                                        ['%event%' => $event['event_name'], '%link%' => $link]
                                    ); ?>
                                    <?php if (!empty($errors)): ?>
                                    <i class="fa fa-warning text-danger" data-toggle="tooltip" title="<?php echo $errors; ?>"></i>
                                    <?php endif; ?>
                                    <p class="fs-12 dark-sm timeline-campaign-event-date-<?php echo $event['event_id']; ?>"><?php echo $view['date']->toFull($event['trigger_date'], 'utc'); ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        <div class="pa-sm">
            <?php $tags = $lead->getTags(); ?>
            <?php foreach ($tags as $tag): ?>
                <h5 class="pull-left mt-xs mr-xs"><span class="label label-success"><?php echo $tag->getTag(); ?></span>
                </h5>
            <?php endforeach; ?>
            <div class="clearfix"></div>
        </div>
        <div class="pa-sm panel-companies">
            <div class="panel-title">  <?php echo $view['translator']->trans(
                    'mautic.lead.lead.companies'); ?></div>
            <?php foreach ($companies as $key => $company): ?>
                <h5 class="pull-left mt-xs mr-xs"><span class="label label-success" >
                       <i id="company-<?php echo $company['id']; ?>" class="fa fa-check <?php if ($company['is_primary'] == 1): ?>primary<?php endif?>" onclick="Mautic.setAsPrimaryCompany(<?php echo $company['id']?>, <?php echo $lead->getId()?>);" title="<?php echo $view['translator']->trans('mautic.lead.company.set.primary'); ?>"></i> <a href="<?php echo $view['router']->path('mautic_company_action', ['objectAction' => 'edit', 'objectId' => $company['id']]); ?>" style="color: white;"><?php echo $company['companyname']; ?></a>
                    </span>
                </h5>
            <?php endforeach; ?>
            <div class="clearfix"></div>
        </div>
    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->
