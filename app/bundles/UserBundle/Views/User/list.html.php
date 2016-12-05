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
    $view->extend('MauticUserBundle:User:index.html.php');
endif;
?>
<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered user-list" id="userTable">
        <thead>
        <tr>
            <?php
            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'checkall'        => 'true',
                    'target'          => '#userTable',
                    'langVar'         => 'user.user',
                    'routeBase'       => 'user',
                    'templateButtons' => [
                        'delete' => $permissions['delete'],
                    ],
                ]
            );
            ?>
            <th class="visible-md visible-lg col-user-avatar"></th>
            <?php
            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'sessionVar' => 'user',
                    'orderBy'    => 'u.lastName, u.firstName, u.username',
                    'text'       => 'mautic.core.name',
                    'class'      => 'col-user-name',
                    'default'    => true,
                ]
            );

            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'sessionVar' => 'user',
                    'orderBy'    => 'u.username',
                    'text'       => 'mautic.core.username',
                    'class'      => 'col-user-username',
                ]
            );

            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'sessionVar' => 'user',
                    'orderBy'    => 'u.email',
                    'text'       => 'mautic.core.type.email',
                    'class'      => 'visible-md visible-lg col-user-email',
                ]
            );

            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'sessionVar' => 'user',
                    'orderBy'    => 'r.name',
                    'text'       => 'mautic.user.role',
                    'class'      => 'visible-md visible-lg col-user-role',
                ]
            );

            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'sessionVar' => 'user',
                    'orderBy'    => 'u.id',
                    'text'       => 'mautic.core.id',
                    'class'      => 'visible-md visible-lg col-user-id',
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
                            'routeBase' => 'user',
                            'langVar'   => 'user.user',
                            'pull'      => 'left',
                        ]
                    );
                    ?>
                </td>
                <td class="visible-md visible-lg">
                    <img class="img img-responsive img-thumbnail w-44" src="<?php echo $view['gravatar']->getImage($item->getEmail(), '50'); ?>"/>
                </td>
                <td>
                    <div>
                        <?php if ($permissions['edit']) : ?>
                            <a href="<?php echo $view['router']->path(
                                'mautic_user_action',
                                ['objectAction' => 'edit', 'objectId' => $item->getId()]
                            ); ?>" data-toggle="ajax">
                                <?php echo $item->getName(true); ?>
                            </a>
                        <?php else : ?>
                            <?php echo $item->getName(true); ?>
                        <?php endif; ?>
                    </div>
                    <div class="small"><em><?php echo $item->getPosition(); ?></em></div>
                </td>
                <td><?php echo $item->getUsername(); ?></td>
                <td class="visible-md visible-lg">
                    <a href="mailto: <?php echo $item->getEmail(); ?>"><?php echo $item->getEmail(); ?></a>
                </td>
                <td class="visible-md visible-lg"><?php echo $item->getRole()->getName(); ?></td>
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
                'baseUrl'    => $view['router']->path('mautic_user_index'),
                'sessionVar' => 'user',
            ]
        ); ?>
    </div>
</div>
