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
if ('index' == $tmpl):
    $view->extend('MauticTagManagerBundle:Tag:index.html.php');
endif;

if (!isset($nameGetter)) {
    $nameGetter = 'getTag';
}

$listCommand = $view['translator']->trans('mautic.tagmanager.tag.searchcommand.list');
?>

<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="tagsTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#tagsTable',
                        'langVar'         => 'tagmanager.tag',
                        'routeBase'       => 'tagmanager',
                        'templateButtons' => [
                            'delete' => $permissions['tagManager:tagManager:delete'],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'tags',
                        'orderBy'    => 'lt.tag',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-tag-name',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'tags',
                        'text'       => 'mautic.lead.list.thead.leadcount',
                        'class'      => 'visible-md visible-lg col-tag-leadcount',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'tags',
                        'orderBy'    => 'lt.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-tag-id',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php $mauticTemplateVars['item'] = $item; ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => [
                                    'edit'   => $permissions['tagManager:tagManager:edit'],
                                    'delete' => $permissions['tagManager:tagManager:delete'],
                                ],
                                'routeBase'  => 'tagmanager',
                                'langVar'    => 'tagmanager.tag',
                                'nameGetter' => $nameGetter,
                                'custom'     => [
                                    [
                                        'attr' => [
                                            'data-toggle' => 'ajax',
                                            'href'        => $view['router']->path(
                                                'mautic_contact_index',
                                                [
                                                    'search' => "$listCommand:{$item->getTag()}",
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
                            <?php if ($permissions['tagManager:tagManager:edit']) : ?>
                                <a href="<?php echo $view['router']->path(
                                    'mautic_tagmanager_action',
                                    ['objectAction' => 'view', 'objectId' => $item->getId()]
                                ); ?>" data-toggle="ajax">
                                    <?php echo $item->getTag(); ?>
                                </a>
                            <?php else : ?>
                                <?php echo $item->getTag(); ?>
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
                            ['search' => $view['translator']->trans('mautic.tagmanager.lead.searchcommand.list').':"'.$item->getTag().'"']
                        ); ?>" data-toggle="ajax"<?php echo (0 == $tagsCount[$item->getId()]) ? 'disabled=disabled' : ''; ?>>
                            <?php echo $view['translator']->trans(
                                'mautic.lead.list.viewleads_count',
                                ['%count%' => $tagsCount[$item->getId()]]
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
                    'baseUrl'    => $view['router']->path('mautic_tagmanager_index'),
                    'sessionVar' => 'tagmanager',
                ]
            ); ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
