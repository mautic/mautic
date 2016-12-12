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
    $view->extend('MauticUserBundle:Role:index.html.php');
endif;
?>

<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered role-list" id="roleTable">
        <thead>
        <tr>
            <?php
            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'checkall'        => 'true',
                    'target'          => '#roleTable',
                    'langVar'         => 'user.role',
                    'routeBase'       => 'role',
                    'templateButtons' => [
                        'delete' => $permissions['delete'],
                    ],
                ]
            );

            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'sessionVar' => 'role',
                    'orderBy'    => 'r.name',
                    'text'       => 'mautic.core.name',
                    'class'      => 'col-role-name',
                    'default'    => true,
                ]
            );
            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'sessionVar' => 'role',
                    'orderBy'    => 'r.description',
                    'text'       => 'mautic.core.description',
                    'class'      => 'visible-md visible-lg col-role-desc',
                ]
            );
            ?>
            <th class="visible-md visible-lg col-rolelist-usercount">
                <?php echo $view['translator']->trans('mautic.user.role.list.thead.usercount'); ?>
            </th>
            <?php
            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'sessionVar' => 'role',
                    'orderBy'    => 'r.id',
                    'text'       => 'mautic.core.id',
                    'class'      => 'visible-md visible-lg col-role-id',
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
                                'edit'   => $permissions['edit'],
                                'delete' => $permissions['delete'],
                            ],
                            'routeBase' => 'role',
                            'langVar'   => 'user.role',
                            'pull'      => 'left',
                        ]
                    );
                    ?>
                </td>
                <td>
                    <?php if ($permissions['edit']) : ?>
                        <a href="<?php echo $view['router']->path(
                            'mautic_role_action',
                            ['objectAction' => 'edit', 'objectId' => $item->getId()]
                        ); ?>" data-toggle="ajax">
                            <?php echo $item->getName(); ?>
                        </a>
                    <?php else : ?>
                        <?php echo $item->getName(); ?>
                    <?php endif; ?>
                </td>
                <td class="visible-md visible-lg">
                    <?php echo $item->getDescription(); ?>
                </td>
                <td class="visible-md visible-lg">
                    <a class="label label-primary" href="<?php echo $view['router']->path(
                        'mautic_user_index',
                        ['search' => $view['translator']->trans('mautic.user.user.searchcommand.role').':&quot;'.$item->getName().'&quot;']
                    ); ?>" data-toggle="ajax"<?php echo ($userCounts[$item->getId()] == 0) ? 'disabled=disabled' : ''; ?>>
                        <?php echo $view['translator']->transChoice(
                            'mautic.user.role.list.viewusers_count',
                            $userCounts[$item->getId()],
                            ['%count%' => $userCounts[$item->getId()]]
                        ); ?>
                    </a>
                </td>
                <td class="visible-md visible-lg">
                    <?php echo $item->getId(); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="panel-footer">
    <?php echo $view->render(
        'MauticCoreBundle:Helper:pagination.html.php',
        [
            'totalItems' => count($items),
            'page'       => $page,
            'limit'      => $limit,
            'baseUrl'    => $view['router']->path('mautic_role_index'),
            'sessionVar' => 'role',
        ]
    ); ?>
</div>

