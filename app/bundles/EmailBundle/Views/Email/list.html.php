<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticEmailBundle:Email:index.html.php');
}
?>

<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered email-list">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'routeBase'       => 'email',
                        'templateButtons' => [
                            'delete' => $permissions['email:emails:deleteown'] || $permissions['email:emails:deleteother'],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'email',
                        'orderBy'    => 'e.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-email-name',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'email',
                        'orderBy'    => 'c.title',
                        'text'       => 'mautic.core.category',
                        'class'      => 'visible-md visible-lg col-email-category',
                    ]
                );
                ?>

                <th class="visible-sm visible-md visible-lg col-email-stats"><?php echo $view['translator']->trans('mautic.core.stats'); ?></th>

                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'email',
                        'orderBy'    => 'e.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-email-id',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $hasVariants     = $item->isVariant();
                $hasTranslations = $item->isTranslation();
                $type            = $item->getEmailType();
                ?>
                <tr>
                    <td>
                        <?php
                        $edit = $view['security']->hasEntityAccess(
                            $permissions['email:emails:editown'],
                            $permissions['email:emails:editother'],
                            $item->getCreatedBy()
                        );
                        $customButtons = ($type == 'list') ? [
                            [
                                'attr' => [
                                    'data-toggle' => 'ajax',
                                    'href'        => $view['router']->path(
                                        'mautic_email_action',
                                        ['objectAction' => 'send', 'objectId' => $item->getId()]
                                    ),
                                ],
                                'iconClass' => 'fa fa-send-o',
                                'btnText'   => 'mautic.email.send',
                            ],
                        ] : [];
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => [
                                    'edit'   => $edit,
                                    'clone'  => $permissions['email:emails:create'],
                                    'delete' => $view['security']->hasEntityAccess(
                                        $permissions['email:emails:deleteown'],
                                        $permissions['email:emails:deleteother'],
                                        $item->getCreatedBy()
                                    ),
                                    'abtest' => (!$hasVariants && $edit && $permissions['email:emails:create']),
                                ],
                                'routeBase'     => 'email',
                                'customButtons' => $customButtons,
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <div>
                            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php', ['item' => $item, 'model' => 'email']); ?>
                            <a href="<?php echo $view['router']->path(
                                'mautic_email_action',
                                ['objectAction' => 'view', 'objectId' => $item->getId()]
                            ); ?>" data-toggle="ajax">
                                <?php echo $item->getName(); ?>
                                <?php if ($hasVariants): ?>
                                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.icon_tooltip.ab_test'); ?>">
                                    <i class="fa fa-fw fa-sitemap"></i>
                                </span>
                                <?php endif; ?>
                                <?php if ($hasTranslations): ?>
                                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans(
                                        'mautic.core.icon_tooltip.translation'
                                    ); ?>">
                                    <i class="fa fa-fw fa-language"></i>
                                </span>
                                <?php endif; ?>
                                <?php if ($type == 'list'): ?>
                                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans(
                                        'mautic.email.icon_tooltip.list_email'
                                    ); ?>">
                                    <i class="fa fa-fw fa-pie-chart"></i>
                                </span>
                                <?php endif; ?>
                                <?php echo $view['content']->getCustomContent('email.name', $mauticTemplateVars); ?>
                            </a>
                        </div>
                        <?php if ($description = $item->getDescription()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $description; ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <?php $category = $item->getCategory(); ?>
                        <?php $catName  = ($category) ? $category->getTitle() : $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                        <?php $color    = ($category) ? '#'.$category->getColor() : 'inherit'; ?>
                        <span style="white-space: nowrap;">
                            <span class="label label-default pa-4" style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span>
                        </span>
                    </td>
                    <td class="visible-sm visible-md visible-lg col-stats">
                        <?php $pending = $model->getPendingLeads($item, null, true); ?>
                        <?php if ($type == 'list' && !empty($pending)): ?>
                        <span class="mt-xs label label-default"
                              data-toggle="tooltip"
                              title="<?php echo $view['translator']->trans('mautic.email.stat.leadcount.tooltip'); ?>">
                            <?php echo $view['translator']->trans(
                                'mautic.email.stat.leadcount',
                                ['%count%' => $pending]
                            ); ?>
                        </span>
                        <?php endif; ?>
                        <?php $queued = $model->getQueuedCounts($item); ?>
                        <?php if (!empty($queued)): ?>
                        <span class="mt-xs label label-default"
                              data-toggle="tooltip"
                              title="<?php echo $view['translator']->trans('mautic.email.stat.queued.tooltip'); ?>">
                            <?php echo $view['translator']->trans(
                                'mautic.email.stat.queued',
                                ['%count%' => $queued]
                            ); ?>
                        </span>
                        <?php endif; ?>
                        <span class="mt-xs label label-warning">
                            <?php echo $view['translator']->trans(
                                'mautic.email.stat.sentcount',
                                ['%count%' => $item->getSentCount(true)]
                            ); ?>
                        </span>
                        <span class="mt-xs label label-success">
                            <?php echo $view['translator']->trans(
                                'mautic.email.stat.readcount',
                                ['%count%' => $item->getReadCount(true)]
                            ); ?>
                        </span>
                        <span class="mt-xs label label-primary">
                            <?php echo $view['translator']->trans(
                                'mautic.email.stat.readpercent',
                                ['%count%' => $item->getReadPercentage(true)]
                            ); ?>
                        </span>
                        <?php echo $view['content']->getCustomContent('email.stats', $mauticTemplateVars); ?>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            [
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'baseUrl'    => $view['router']->path('mautic_email_index'),
                'sessionVar' => 'email',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
