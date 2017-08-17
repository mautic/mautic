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
                            'delete' => $permissions['point:scoringCategory:delete'],
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
                        $templateButtonsActions = [];
                        if (!$item->isGlobalScore()) { // we want to display the checkbox with no actions...
                            $templateButtonsActions = [
                                'edit'   => $permissions['point:scoringCategory:edit'],
                                'clone'  => $permissions['point:scoringCategory:create'],
                                'delete' => $permissions['point:scoringCategory:delete'],
                            ];
                        }
                        echo $view->render(
                            //'MauticCoreBundle:Helper:list_actions.html.php',
                            'MauticScoringBundle:ScoringCategory:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => $templateButtonsActions,
                                'routeBase'       => 'scoring',
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <div>
                            <?php if ($item->isGlobalScore()) {
                            ?>
                                <i class="fa fa-fw fa-lg fa-toggle-on text-muted has-click-event scoring-scoringcategory-publish-icon-global"
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
                                ['item' => $item, 'model' => 'scoring.scoringcategory']
                            ); ?>
                            <a href="<?php echo $view['router']->path(
                                'mautic_scoring_action',
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
