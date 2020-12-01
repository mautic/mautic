<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ('index' == $tmpl) {
    $view->extend('MauticSocialBundle:Monitoring:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered monitoring-list" id="monitoringTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#monitoringTable',
                        'langVar'         => 'mautic.social.monitoring',
                        'routeBase'       => 'social',
                        'templateButtons' => [
                            'delete' => $view['security']->isGranted('mauticSocial:monitoring:delete'),
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'social.monitoring',
                        'orderBy'    => 'e.title',
                        'text'       => 'mautic.core.title',
                        'class'      => 'col-monitoring-title',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'social.monitoring',
                        'orderBy'    => 'e.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-asset-id',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $k => $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => [
                                    'edit'   => $view['security']->isGranted('mauticSocial:monitoring:edit'),
                                    'delete' => $view['security']->isGranted('mauticSocial:monitoring:delete'),
                                ],
                                'routeBase'  => 'social',
                                'langVar'    => 'mautic.social.monitoring',
                                'nameGetter' => 'getTitle',
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <div>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                [
                                    'item'  => $item,
                                    'model' => 'social.monitoring',
                                ]
                            ); ?>
                            <a href="<?php echo $view['router']->path(
                                'mautic_social_action',
                                ['objectAction' => 'view', 'objectId' => $item->getId()]
                            ); ?>"
                               data-toggle="ajax">
                                <?php echo $item->getTitle(); ?>
                            </a>
                        </div>
                        <?php if ($description = $item->getDescription()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $description; ?></small>
                            </div>
                        <?php endif; ?>
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
                'menuLinkId' => 'mautic_campaign_index',
                'baseUrl'    => $view['router']->path('mautic_social_index'),
                'sessionVar' => 'social.monitoring',
                'routeBase'  => 'social',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'mautic.mautic.social.monitoring.noresults.tip']); ?>
<?php endif; ?>

<?php echo $view->render(
    'MauticCoreBundle:Helper:modal.html.php',
    [
        'id'     => 'MonitoringPreviewModal',
        'header' => false,
    ]
);
