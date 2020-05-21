
<?php

$baseUrl = $view['router']->path(
    'mautic_company_contacts_list',
    [
        'objectId' => $company->getId(),
    ]
);

?>

<?php if (count($contacts)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="leadTable">
            <thead>
                <tr>
                    <?php
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