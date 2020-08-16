
<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$baseUrl = $view['router']->path(
    'mautic_company_contacts_list',
    [
        'objectId' => $company->getId(),
    ]
);

$customButtons = [];
if ($permissions['lead:leads:editown'] || $permissions['lead:leads:editother']) {
    $customButtons = [
        [
            'attr' => [
                'class'       => 'btn btn-default btn-sm btn-nospin',
                'data-toggle' => 'ajaxmodal',
                'data-target' => '#MauticSharedModal',
                'href'        => $view['router']->path('mautic_segment_batch_contact_view'),
                'data-header' => $view['translator']->trans('mautic.lead.batch.lists'),
            ],
            'btnText'   => $view['translator']->trans('mautic.lead.batch.lists'),
            'iconClass' => 'fa fa-pie-chart',
        ],
        [
            'attr' => [
                'class'       => 'btn btn-default btn-sm btn-nospin',
                'data-toggle' => 'ajaxmodal',
                'data-target' => '#MauticSharedModal',
                'href'        => $view['router']->path('mautic_contact_action', ['objectAction' => 'batchStages']),
                'data-header' => $view['translator']->trans('mautic.lead.batch.stages'),
            ],
            'btnText'   => $view['translator']->trans('mautic.lead.batch.stages'),
            'iconClass' => 'fa fa-tachometer',
        ],
        [
            'attr' => [
                'class'       => 'btn btn-default btn-sm btn-nospin',
                'data-toggle' => 'ajaxmodal',
                'data-target' => '#MauticSharedModal',
                'href'        => $view['router']->path('mautic_contact_action', ['objectAction' => 'batchCampaigns']),
                'data-header' => $view['translator']->trans('mautic.lead.batch.campaigns'),
            ],
            'btnText'   => $view['translator']->trans('mautic.lead.batch.campaigns'),
            'iconClass' => 'fa fa-clock-o',
        ],
        [
            'attr' => [
                'class'       => 'btn btn-default btn-sm btn-nospin',
                'data-toggle' => 'ajaxmodal',
                'data-target' => '#MauticSharedModal',
                'href'        => $view['router']->path('mautic_contact_action', ['objectAction' => 'batchOwners']),
                'data-header' => $view['translator']->trans('mautic.lead.batch.owner'),
            ],
            'btnText'   => $view['translator']->trans('mautic.lead.batch.owner'),
            'iconClass' => 'fa fa-user',
        ],
        [
            'attr' => [
                'class'       => 'btn btn-default btn-sm btn-nospin',
                'data-toggle' => 'ajaxmodal',
                'data-target' => '#MauticSharedModal',
                'href'        => $view['router']->path('mautic_contact_action', ['objectAction' => 'batchDnc']),
                'data-header' => $view['translator']->trans('mautic.lead.batch.dnc'),
            ],
            'btnText'   => $view['translator']->trans('mautic.lead.batch.dnc'),
            'iconClass' => 'fa fa-ban text-danger',
        ],
    ];
}

?>

<?php if (count($contacts)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="leadTable">
            <thead>
                <tr>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'checkall'        => 'true',
                        'target'          => '#contacts-table',
                        'templateButtons' => [
                            'delete' => $permissions['lead:leads:deleteown'] || $permissions['lead:leads:deleteother'],
                        ],
                        'customButtons' => $customButtons,
                        'langVar'       => 'lead.lead',
                        'routeBase'     => 'contact',
                        'tooltip'       => $view['translator']->trans('mautic.lead.list.checkall.help'),
                    ]);

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'sessionVar' => 'company.'.$company->getId().'.contacts',
                        'orderBy'    => 'l.lastname, l.firstname, l.company, l.email',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-lead-name',
                        'target'     => '#contacts-table',
                        'baseUrl'    => $baseUrl,
                    ]);

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'sessionVar' => 'company.'.$company->getId().'.contacts',
                        'orderBy'    => 'l.email',
                        'text'       => 'mautic.core.type.email',
                        'class'      => 'col-lead-email visible-md visible-lg',
                        'target'     => '#contacts-table',
                        'baseUrl'    => $baseUrl,
                    ]);

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'sessionVar' => 'company.'.$company->getId().'.contacts',
                        'orderBy'    => 'l.city, l.state',
                        'text'       => 'mautic.lead.lead.thead.location',
                        'class'      => 'col-lead-location visible-md visible-lg',
                        'target'     => '#contacts-table',
                        'baseUrl'    => $baseUrl,
                    ]);
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'sessionVar' => 'company.'.$company->getId().'.contacts',
                        'orderBy'    => 'l.stage_id',
                        'text'       => 'mautic.lead.stage.label',
                        'class'      => 'col-lead-stage',
                        'target'     => '#contacts-table',
                        'baseUrl'    => $baseUrl,
                    ]);
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'sessionVar' => 'company.'.$company->getId().'.contacts',
                        'orderBy'    => 'l.points',
                        'text'       => 'mautic.lead.points',
                        'class'      => 'visible-md visible-lg col-lead-points',
                        'target'     => '#contacts-table',
                        'baseUrl'    => $baseUrl,
                    ]);

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'sessionVar' => 'company.'.$company->getId().'.contacts',
                        'orderBy'    => 'l.last_active',
                        'text'       => 'mautic.lead.lastactive',
                        'class'      => 'col-lead-lastactive visible-md visible-lg',
                        'default'    => true,
                        'target'     => '#contacts-table',
                        'baseUrl'    => $baseUrl,
                    ]);

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'sessionVar' => 'company.'.$company->getId().'.contacts',
                        'orderBy'    => 'l.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'col-lead-id visible-md visible-lg',
                        'target'     => '#contacts-table',
                        'baseUrl'    => $baseUrl,
                    ]);
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($contacts as $contact) : ?>
                <?php $fields = $contact->getFields(); ?>
                <tr>
                    <td>
                        <?php
                        $hasEditAccess = $security->hasEntityAccess(
                            $permissions['lead:leads:editown'],
                            $permissions['lead:leads:editother'],
                            $contact->getPermissionUser()
                        );

                        $custom = [];
                        if ($hasEditAccess && !empty($currentList)) {
                            //this lead was manually added to a list so give an option to remove them
                            $custom[] = [
                                'attr' => [
                                    'href' => $view['router']->path('mautic_segment_action', [
                                        'objectAction' => 'removeLead',
                                        'objectId'     => $currentList['id'],
                                        'leadId'       => $contact->getId(),
                                    ]),
                                    'data-toggle' => 'ajax',
                                    'data-method' => 'POST',
                                ],
                                'btnText'   => 'mautic.lead.lead.remove.fromlist',
                                'iconClass' => 'fa fa-remove',
                            ];
                        }

                        if (!empty($fields['core']['email']['value'])) {
                            $custom[] = [
                                'attr' => [
                                    'data-toggle' => 'ajaxmodal',
                                    'data-target' => '#MauticSharedModal',
                                    'data-header' => $view['translator']->trans('mautic.lead.email.send_email.header', ['%email%' => $fields['core']['email']['value']]),
                                    'href'        => $view['router']->path('mautic_contact_action', ['objectId' => $contact->getId(), 'objectAction' => 'email', 'list' => 1]),
                                ],
                                'btnText'   => 'mautic.lead.email.send_email',
                                'iconClass' => 'fa fa-send',
                            ];
                        }

                        echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', [
                            'item'            => $contact,
                            'templateButtons' => [
                                'edit'   => $hasEditAccess,
                                'delete' => $security->hasEntityAccess($permissions['lead:leads:deleteown'], $permissions['lead:leads:deleteother'], $contact->getPermissionUser()),
                            ],
                            'routeBase'     => 'contact',
                            'langVar'       => 'lead.lead',
                            'customButtons' => $custom,
                        ]);
                        ?>
                    </td>
                    <td>
                        <a href="<?php echo $view['router']->path('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $contact->getId()]); ?>" data-toggle="ajax">

                            <div><?php echo $view->escape($contact->isAnonymous() ? $view['translator']->trans($contact->getPrimaryIdentifier()) : $contact->getPrimaryIdentifier()); ?></div>
                            <div class="small"><?php echo $view->escape($contact->getSecondaryIdentifier()); ?></div>
                        </a>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $view->escape($fields['core']['email']['value']); ?></td>
                    <td class="visible-md visible-lg">
                        <?php
                        $flag = (!empty($fields['core']['country'])) ? $view['assets']->getCountryFlag($fields['core']['country']['value']) : '';
                        if (!empty($flag)) :
                            ?>
                        <img src="<?php echo $flag; ?>" style="max-height: 24px;" class="mr-sm" />
                            <?php
                        endif;
                        $location = [];
                        if (!empty($fields['core']['city']['value'])) :
                            $location[] = $fields['core']['city']['value'];
                        endif;
                        if (!empty($fields['core']['state']['value'])) :
                            $location[] = $fields['core']['state']['value'];
                        elseif (!empty($fields['core']['country']['value'])) :
                            $location[] = $fields['core']['country']['value'];
                        endif;
                        echo $view->escape(implode(', ', $location));
                        ?>
                        <div class="clearfix"></div>
                    </td>
                    <td class="text-center">
                        <?php
                        $color = $contact->getColor();
                        $style = !empty($color) ? ' style="background-color: '.$color.';"' : '';
                        ?>
                        <?php if ($contact->getStage()) :?>
                        <span class="label label-default"<?php echo $style; ?>><?php echo $view->escape($contact->getStage()->getName()); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg text-center">
                        <?php
                        $color = $contact->getColor();
                        $style = !empty($color) ? ' style="background-color: '.$color.';"' : '';
                        ?>
                        <span class="label label-default"<?php echo $style; ?>><?php echo $contact->getPoints(); ?></span>
                    </td>
                    <td class="visible-md visible-lg">
                        <abbr title="<?php echo $view['date']->toFull($contact->getLastActive()); ?>">
                            <?php echo $view['date']->toText($contact->getLastActive()); ?>
                        </abbr>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $contact->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php echo $view->render(
        'MauticCoreBundle:Helper:pagination.html.php',
        [
            'page'       => $page,
            'limit'      => $limit,
            'baseUrl'    => $baseUrl,
            'target'     => '#contacts-table',
            'totalItems' => $totalItems,
            'sessionVar' => 'company.'.$company->getId().'.contacts',
        ]
    ); ?>
<?php else : ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>