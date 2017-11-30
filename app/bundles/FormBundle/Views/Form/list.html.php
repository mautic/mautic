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
    $view->extend('MauticFormBundle:Form:index.html.php');
}

?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="formTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#formTable',
                        'routeBase'       => 'form',
                        'templateButtons' => [
                            'delete' => $permissions['form:forms:deleteown'] || $permissions['form:forms:deleteother'],
                        ],
                        'customButtons' => [
                            [
                                'confirm' => [
                                    'message'       => $view['translator']->trans('mautic.form.confirm_batch_rebuild'),
                                    'confirmText'   => $view['translator']->trans('mautic.form.rebuild'),
                                    'confirmAction' => $view['router']->path(
                                        'mautic_form_action',
                                        array_merge(['objectAction' => 'batchRebuildHtml'])
                                    ),
                                    'iconClass'       => 'fa fa-fw fa-refresh',
                                    'btnText'         => $view['translator']->trans('mautic.form.rebuild'),
                                    'precheck'        => 'batchActionPrecheck',
                                    'confirmCallback' => 'executeBatchAction',
                                ],
                                'primary' => true,
                            ],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'form',
                        'orderBy'    => 'f.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-form-name',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'form',
                        'orderBy'    => 'c.title',
                        'text'       => 'mautic.core.category',
                        'class'      => 'visible-md visible-lg col-form-category',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'form',
                        'orderBy'    => 'submission_count',
                        'text'       => 'mautic.form.form.results',
                        'class'      => 'visible-md visible-lg col-form-submissions',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'form',
                        'orderBy'    => 'f.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-form-id',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i): ?>
                <?php $item = $i[0]; ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => [
                                    'edit' => $security->hasEntityAccess(
                                        $permissions['form:forms:editown'],
                                        $permissions['form:forms:editother'],
                                        $item->getCreatedBy()
                                    ),
                                    'clone'  => $permissions['form:forms:create'],
                                    'delete' => $security->hasEntityAccess(
                                        $permissions['form:forms:deleteown'],
                                        $permissions['form:forms:deleteother'],
                                        $item->getCreatedBy()
                                    ),
                                ],
                                'routeBase'     => 'form',
                                'customButtons' => [
                                    [
                                        'attr' => [
                                            'data-toggle' => '',
                                            'target'      => '_blank',
                                            'href'        => $view['router']->path(
                                                'mautic_form_action',
                                                ['objectAction' => 'preview', 'objectId' => $item->getId()]
                                            ),
                                        ],
                                        'iconClass' => 'fa fa-camera',
                                        'btnText'   => 'mautic.form.form.preview',
                                    ],
                                    [
                                        'attr' => [
                                            'data-toggle' => 'ajax',
                                            'href'        => $view['router']->path(
                                                'mautic_form_action',
                                                ['objectAction' => 'results', 'objectId' => $item->getId()]
                                            ),
                                        ],
                                        'iconClass' => 'fa fa-database',
                                        'btnText'   => 'mautic.form.form.results',
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
                                ['item' => $item, 'model' => 'form.form']
                            ); ?>
                            <a href="<?php echo $view['router']->path(
                                'mautic_form_action',
                                ['objectAction' => 'view', 'objectId' => $item->getId()]
                            ); ?>" data-toggle="ajax" data-menu-link="mautic_form_index">
                                <?php echo $item->getName(); ?>
                                <?php if ($item->getFormType() == 'campaign'): ?>
                                    <span data-toggle="tooltip" title="<?php echo $view['translator']->trans(
                                        'mautic.form.icon_tooltip.campaign_form'
                                    ); ?>"><i class="fa fa-fw fa-cube"></i></span>
                                <?php endif; ?>
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
                        <span style="white-space: nowrap;"><span class="label label-default pa-4" style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span></span>
                    </td>
                    <td class="visible-md visible-lg">
                        <a href="<?php echo $view['router']->path(
                            'mautic_form_action',
                            ['objectAction' => 'results', 'objectId' => $item->getId()]
                        ); ?>" data-toggle="ajax" data-menu-link="mautic_form_index" class="btn btn-primary btn-xs" <?php echo ($i['submission_count']
                            == 0) ? 'disabled=disabled' : ''; ?>>
                            <?php echo $view['translator']->transChoice(
                                'mautic.form.form.viewresults',
                                $i['submission_count'],
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
                    'totalItems' => $totalItems,
                    'page'       => $page,
                    'limit'      => $limit,
                    'baseUrl'    => $view['router']->path('mautic_form_index'),
                    'sessionVar' => 'form',
                ]
            ); ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'mautic.form.noresults.tip']); ?>
<?php endif; ?>
