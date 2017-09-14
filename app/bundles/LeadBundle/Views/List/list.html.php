<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
//Check to see if the entire page should be displayed or just main content
if ($tmpl == 'index'):
    $view->extend('MauticLeadBundle:List:index.html.php');
endif;
$listCommand = $view['translator']->trans('mautic.lead.lead.searchcommand.list');
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
                        'sessionVar' => 'segment',
                        'orderBy'    => 'l.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-leadlist-name',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'segment',
                        'text'       => 'mautic.lead.list.thead.leadcount',
                        'class'      => 'visible-md visible-lg col-leadlist-leadcount',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'segment',
                        'orderBy'    => 'l.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-leadlist-id',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => [
                                    'edit'   => $view['security']->hasEntityAccess(true, $permissions['lead:lists:editother'], $item->getCreatedBy()),
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
                                    ['objectAction' => 'edit', 'objectId' => $item->getId()]
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
                        </div>
                        <?php if ($description = $item->getDescription()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $description; ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <a class="label label-primary" href="<?php echo $view['router']->path(
                            'mautic_contact_index',
                            ['search' => $view['translator']->trans('mautic.lead.lead.searchcommand.list').':'.$item->getAlias()]
                        ); ?>" data-toggle="ajax"<?php echo ($leadCounts[$item->getId()] == 0) ? 'disabled=disabled' : ''; ?>>
                            <?php echo $view['translator']->transChoice(
                                'mautic.lead.list.viewleads_count',
                                $leadCounts[$item->getId()],
                                ['%count%' => $leadCounts[$item->getId()]]
                            ); ?>
                        </a>
                    </td>
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
                    'sessionVar' => 'segment',
                ]
            ); ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
