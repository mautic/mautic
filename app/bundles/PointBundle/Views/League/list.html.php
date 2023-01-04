<?php

if ('index' == $tmpl) {
    $view->extend('MauticPointBundle:League:index.html.php');
}
?>

<?php if (count($items)): ?>
    <div class="table-responsive page-list">
        <table class="table table-hover table-striped table-bordered league-list" id="leagueTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#leagueTable',
                        'routeBase'       => 'league',
                        'templateButtons' => [
                            'delete' => $permissions['point:league:delete'],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'league',
                        'orderBy'    => 's.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-league-name',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'league',
                        'orderBy'    => 's.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-league-id',
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
                        $templateButtonsActions = [];
                        if (!$item->isGlobalScore()) { // we want to display the checkbox with no actions...
                            $templateButtonsActions = [
                                'edit'   => $permissions['point:league:edit'],
                                'clone'  => $permissions['point:league:create'],
                                'delete' => $permissions['point:league:delete'],
                            ];
                        }
                        echo $view->render(
                        //'MauticCoreBundle:Helper:list_actions.html.php',
                            'MauticPointBundle:League:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => $templateButtonsActions,
                                'routeBase'       => 'league',
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <div>
                            <?php if ($item->isGlobalScore()) {
                            ?>
                                <i class="fa fa-fw fa-lg fa-toggle-on text-muted has-click-event point-league-publish-icon-global"
                                   data-toggle="tooltip"
                                   data-container="body"
                                   data-placement="right"
                                   data-status="published"
                                   title=""
                                   data-original-title="Published"></i>
                                <span>
                                <?php echo $item->getName(); ?>
                            </span>
                                <?php
                        } else {
                            echo $view->render(
                                    'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                    ['item' => $item, 'model' => 'point.league']
                                ); ?>
                            <a href="<?php echo $view['router']->path(
                                'mautic_point.league_action',
                                ['objectAction' => 'edit', 'objectId' => $item->getId()]
                            ); ?>" data-toggle="ajax">
                                <?php echo $item->getName(); ?>
                                </a><?php
                        } ?>
                        </div>
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
                'totalItems' => count($items),
                'page'       => $page,
                'limit'      => $limit,
                'menuLinkId' => 'mautic_point.league_index',
                'baseUrl'    => $view['router']->path('mautic_point.league_index'),
                'sessionVar' => 'league',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render(
        'MauticCoreBundle:Helper:noresults.html.php',
        ['tip' => 'mautic.point.league.noresults.tip']
    ); ?>
<?php endif; ?>