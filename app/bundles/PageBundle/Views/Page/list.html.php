<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ('index' == $tmpl) {
    $view->extend('MauticPageBundle:Page:index.html.php');
}
?>

<?php if (count($items)): ?>
    <div class="table-responsive page-list">
        <table class="table table-hover table-striped table-bordered pagetable-list" id="pageTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#pageTable',
                        'routeBase'       => 'page',
                        'templateButtons' => [
                            'delete' => $permissions['page:pages:deleteown'] || $permissions['page:pages:deleteother'],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.title',
                        'text'       => 'mautic.core.title',
                        'class'      => 'col-page-title',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'page',
                        'orderBy'    => 'c.title',
                        'text'       => 'mautic.core.category',
                        'class'      => 'visible-md visible-lg col-page-category',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.hits',
                        'text'       => 'mautic.page.thead.hits',
                        'class'      => 'col-page-hits visible-md visible-lg',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.dateAdded',
                        'text'       => 'mautic.lead.import.label.dateAdded',
                        'class'      => 'col-page-dateAdded visible-md visible-lg',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.dateModified',
                        'text'       => 'mautic.lead.import.label.dateModified',
                        'class'      => 'col-page-dateModified visible-md visible-lg',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.createdByUser',
                        'text'       => 'mautic.core.createdby',
                        'class'      => 'col-page-createdByUser visible-md visible-lg',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'page',
                        'orderBy'    => 'submission_count',
                        'text'       => 'mautic.form.form.results',
                        'class'      => 'visible-md visible-lg col-page-submissions',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'col-page-id visible-md visible-lg',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($items as $i):
                $item = $i[0];
            ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => [
                                    'edit' => $view['security']->hasEntityAccess(
                                        $permissions['page:pages:editown'],
                                        $permissions['page:pages:editother'],
                                        $item->getCreatedBy()
                                    ),
                                    'clone'  => $permissions['page:pages:create'],
                                    'delete' => $view['security']->hasEntityAccess(
                                        $permissions['page:pages:deleteown'],
                                        $permissions['page:pages:deleteother'],
                                        $item->getCreatedBy()
                                    ),
                                ],
                                'routeBase'  => 'page',
                                'nameGetter' => 'getTitle',
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php', ['item' => $item, 'model' => 'page.page']); ?>
                        <a href="<?php echo $view['router']->path(
                            'mautic_page_action',
                            ['objectAction' => 'view', 'objectId' => $item->getId()]
                        ); ?>" data-toggle="ajax">
                            <?php echo $item->getTitle(); ?> (<?php echo $item->getAlias(); ?>)
                            <?php
                            $hasVariants        = $item->isVariant();
                            $hasTranslations    = $item->isTranslation();
                            $isPreferenceCenter = $item->isPreferenceCenter();

                            if ($hasVariants || $hasTranslations || $isPreferenceCenter): ?>
                                <span>
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
                                <?php if ($isPreferenceCenter): ?>
                                    <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.icon_tooltip.preference_center'); ?>">
                                        <i class="fa fa-fw fa-cog"></i>
                                    </span>
                                <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td class="visible-md visible-lg">
                        <?php $category = $item->getCategory(); ?>
                        <?php $catName  = ($category) ? $category->getTitle() : $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                        <?php $color    = ($category) ? '#'.$category->getColor() : 'inherit'; ?>
                        <span style="white-space: nowrap;"><span class="label label-default pa-4" style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span></span>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getHits(); ?></td>
                    <td class="visible-md visible-lg" title="<?php echo $item->getDateAdded() ? $view['date']->toFullConcat($item->getDateAdded()) : ''; ?>">
                        <?php echo $item->getDateAdded() ? $view['date']->toDate($item->getDateAdded()) : ''; ?>
                    </td>
                    <td class="visible-md visible-lg" title="<?php echo $item->getDateModified() ? $view['date']->toFullConcat($item->getDateModified()) : ''; ?>">
                        <?php echo $item->getDateModified() ? $view['date']->toDate($item->getDateModified()) : ''; ?>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getCreatedByUser(); ?></td>
                    <td class="visible-md visible-lg">
                        <a href="<?php echo $view['router']->path(
                            'mautic_page_results',
                            ['objectId' => $item->getId()]
                        ); ?>" data-toggle="ajax" data-menu-link="mautic_form_index" class="btn btn-primary btn-xs" <?php echo (0
                        == $i['submission_count']) ? 'disabled=disabled' : ''; ?>>
                            <?php echo $view['translator']->trans(
                                'mautic.form.form.viewresults',
                                ['%count%' => $i['submission_count']]
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
                    'menuLinkId' => 'mautic_page_index',
                    'baseUrl'    => $view['router']->path('mautic_page_index'),
                    'sessionVar' => 'page',
                ]
            ); ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
