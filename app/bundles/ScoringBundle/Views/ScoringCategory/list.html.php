<?php

/*
 * @author      Captivea (QCH)
 */
if ($tmpl == 'index') {
    $view->extend('MauticScoringBundle:ScoringCategory:index.html.php');
}
?>

<?php if (count($items)): ?>
    <div class="table-responsive page-list">
        <table class="table table-hover table-striped table-bordered scoring-list" id="scoringTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#scoringTable',
                        'routeBase'       => 'scoring',
                        'templateButtons' => [
                            'delete' => $permissions['scoring:scoringCategory:delete'],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'scoring',
                        'orderBy'    => 's.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-scoring-name',
                        'default'    => true,
                    ]
                );
                
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'scoring',
                        'orderBy'    => 's.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-scoring-id',
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
                                    'edit'   => $permissions['scoring:scoringCategory:edit'],
                                    'clone'  => $permissions['scoring:scoringCategory:create'],
                                    'delete' => $permissions['scoring:scoringCategory:delete'],
                                ],
                                'routeBase' => 'scoring',
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <div>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                ['item' => $item, 'model' => 'scoring.scoringcategory']
                            ); ?>
                            <a href="<?php echo $view['router']->path(
                                'mautic_scoring_action',
                                ['objectAction' => 'edit', 'objectId' => $item->getId()]
                            ); ?>" data-toggle="ajax">
                                <?php echo $item->getName(); ?>
                            </a>
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
                'menuLinkId' => 'mautic_scoring_index',
                'baseUrl'    => $view['router']->path('mautic_scoring_index'),
                'sessionVar' => 'scoring',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render(
        'MauticCoreBundle:Helper:noresults.html.php',
        ['tip' => 'mautic.scoring.action.noresults.tip']
    ); ?>
<?php endif; ?>
