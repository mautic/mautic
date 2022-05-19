<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CoreBundle\Helper\DateTimeHelper;

//Check to see if the entire page should be displayed or just main content
if ('index' == $tmpl):
    $view->extend('MauticLeadBundle:List:index.html.php');
endif;
$listCommand = $view['translator']->trans('mautic.lead.lead.searchcommand.list');
$now         = (new DateTimeHelper())->getUtcDateTime();
?>

<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="leadListTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#leadListTable',
                        'langVar'         => 'lead.list',
                        'routeBase'       => 'segment',
                        'templateButtons' => [
                            'delete' => $permissions['lead:lists:deleteother'],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'lead.list',
                        'orderBy'    => 'l.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-leadlist-name',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'lead.list',
                        'text'       => 'mautic.lead.list.thead.leadcount',
                        'class'      => 'visible-md visible-lg col-leadlist-leadcount',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'lead.list',
                        'orderBy'    => 'l.dateAdded',
                        'text'       => 'mautic.lead.import.label.dateAdded',
                        'class'      => 'visible-md visible-lg col-leadlist-dateAdded',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'lead.list',
                        'orderBy'    => 'l.dateModified',
                        'text'       => 'mautic.lead.import.label.dateModified',
                        'class'      => 'visible-md visible-lg col-leadlist-dateModified',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'lead.list',
                        'orderBy'    => 'l.createdByUser',
                        'text'       => 'mautic.core.createdby',
                        'class'      => 'visible-md visible-lg col-leadlist-createdByUser',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'lead.list',
                        'orderBy'    => 'l.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-leadlist-id',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php /** @var \Mautic\LeadBundle\Entity\LeadList $item */?>
            <?php foreach ($items as $item): ?>
                <?php
                    $lastBuiltDateDifference = null;
                    if ($item->getLastBuiltDate() instanceof \DateTime) {
                        $lastBuiltDateDifferenceInterval = $now->diff($item->getLastBuiltDate());
                        // Calculate difference between now and last_built_date in hours
                        $lastBuiltDateDifference = (int) abs((new \DateTime())->setTimestamp(0)->add($lastBuiltDateDifferenceInterval)->getTimestamp() / 3600);
                    }
                ?>
                <?php $mauticTemplateVars['item'] = $item; ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => [
                                    'edit'   => $view['security']->hasEntityAccess(true, $permissions['lead:lists:editother'], $item->getCreatedBy()),
                                    'clone'  => $view['security']->hasEntityAccess(true, $permissions['lead:lists:editother'], $item->getCreatedBy()),
                                    'delete' => $view['security']->hasEntityAccess(true, $permissions['lead:lists:deleteother'], $item->getCreatedBy()),
                                ],
                                'routeBase' => 'segment',
                                'langVar'   => 'lead.list',
                                'custom'    => [
                                    [
                                        'attr' => [
                                            'data-toggle' => 'ajax',
                                            'href'        => $view['router']->path(
                                                'mautic_contact_index',
                                                [
                                                    'search' => "$listCommand:{$item->getAlias()}",
                                                ]
                                            ),
                                        ],
                                        'icon'  => 'fa-users',
                                        'label' => 'mautic.lead.list.view_contacts',
                                    ],
                                ],
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <div>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                ['item' => $item, 'model' => 'lead.list']
                            ); ?>
                            <?php if ($view['security']->hasEntityAccess(true, $permissions['lead:lists:editother'], $item->getCreatedBy())) : ?>
                                <a href="<?php echo $view['router']->path(
                                    'mautic_segment_action',
                                    ['objectAction' => 'view', 'objectId' => $item->getId()]
                                ); ?>" data-toggle="ajax">
                                    <?php echo $item->getName(); ?> (<?php echo $item->getAlias(); ?>)
                                </a>
                            <?php else : ?>
                                <?php echo $item->getName(); ?> (<?php echo $item->getAlias(); ?>)
                            <?php endif; ?>
                            <?php if (!$item->isGlobal() && $currentUser->getId() != $item->getCreatedBy()): ?>
                                <br/>
                                <span class="small">(<?php echo $item->getCreatedByUser(); ?>)</span>
                            <?php endif; ?>
                            <?php if ($item->isGlobal()): ?>
                                <i class="fa fa-fw fa-globe"></i>
                            <?php endif; ?>
                            <?php if ($lastBuiltDateDifference >= $segmentRebuildWarningThreshold): ?>
                                <label class="control-label" data-toggle="tooltip"
                                       data-container="body" data-placement="top" title=""
                                       data-original-title="<?php echo $view['translator']->trans(
                                               'mautic.lead.list.form.config.segment_rebuild_time.message',
                                               ['%count%' => $lastBuiltDateDifference]
                                       ); ?>">
                                    <i class="fa text-danger fa-exclamation-circle"></i></label>
                            <?php endif; ?>
                            <?php echo $view['content']->getCustomContent('segment.name', $mauticTemplateVars); ?>
                        </div>
                        <?php if ($description = $item->getDescription()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $description; ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <a class="label label-primary col-count" data-id="<?php echo $item->getId(); ?>" href="<?php echo $view['router']->path(
                            'mautic_contact_index',
                            ['search' => $view['translator']->trans('mautic.lead.lead.searchcommand.list').':'.$item->getAlias()]
                        ); ?>" data-toggle="ajax">
                            <?php echo $view['translator']->trans(
                                'mautic.lead.list.viewleads_count',
                                ['%count%' => $leadCounts[$item->getId()]]
                            ); ?>
                        </a>
                    </td>
                    <td class="visible-md visible-lg" title="<?php echo $item->getDateAdded() ? $view['date']->toFullConcat($item->getDateAdded()) : ''; ?>">
                        <?php echo $item->getDateAdded() ? $view['date']->toDate($item->getDateAdded()) : ''; ?>
                    </td>
                    <td class="visible-md visible-lg" title="<?php echo $item->getDateModified() ? $view['date']->toFullConcat($item->getDateModified()) : ''; ?>">
                        <?php echo $item->getDateModified() ? $view['date']->toDate($item->getDateModified()) : ''; ?>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $view->escape($item->getCreatedByUser()); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="panel-footer">
            <?php echo $view->render(
                'MauticCoreBundle:Helper:pagination.html.php',
                [
                    'totalItems' => count($items),
                    'page'       => $page,
                    'limit'      => $limit,
                    'baseUrl'    => $view['router']->path('mautic_segment_index'),
                    'sessionVar' => 'lead.list',
                ]
            ); ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
