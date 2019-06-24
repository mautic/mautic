<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view['slots']->set('headerTitle', $view['translator']->trans('mautic.core.notifications'));
if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Standard:index.html.php');
}

?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered campaign-list" id="campaignTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#notificationsTable',
                        'routeBase'       => 'account/notifications',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'notification',
                        'orderBy'    => 'n.header',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-campaign-name',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'notification',
                        'orderBy'    => 'cat.title',
                        'text'       => 'mautic.core.description',
                        'class'      => 'visible-md visible-lg col-campaign-category',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <?php $mauticTemplateVars['item'] = $item; ?>
                <tr>
                    <td></td>
                    <td>
                        <div>
                            <a href="<?php echo $view['router']->path(
                                'mautic_user_notification_action',
                                ['objectAction' => 'view', 'objectId' => $item->getId()]
                            ); ?>" data-toggle="ajax">
                                <?php echo $item->getHeader(); ?>
                            <?php echo $view['content']->getCustomContent('notification.', $mauticTemplateVars); ?>
                            </a>
                        </div>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getMessage() ?></td>
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
                'menuLinkId' => 'mautic_notification_index',
                'baseUrl'    => $view['router']->path('mautic_notification_index'),
                'sessionVar' => 'notification',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'mautic.campaign.noresults.tip']); ?>
<?php endif; ?>
