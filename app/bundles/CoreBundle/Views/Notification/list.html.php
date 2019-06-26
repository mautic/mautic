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
if ('index' == $tmpl) {
    $view->extend('MauticCoreBundle:Standard:index.html.php');
}
?>

<script defer type="text/javascript">
    Mautic.notificationIndexLoad( <?php echo json_encode(['mautic.core.yes' => $view['translator']->trans('mautic.core.yes')]); ?> );
</script> 

<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered campaign-list" id="notificationTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'notification',
                        'orderBy'    => 'n.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg',
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
                        'orderBy'    => 'n.message',
                        'text'       => 'mautic.core.description',
                        'class'      => 'visible-md visible-lg',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'notification',
                        'orderBy'    => 'n.isRead',
                        'text'       => 'mautic.core.read',
                        'class'      => 'visible-md visible-lg',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'notification',
                        'orderBy'    => 'n.dateAdded',
                        'text'       => 'mautic.core.date.added',
                        'class'      => 'visible-md visible-lg',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'notification',
                        'text'       => 'mautic.core.actions',
                        'class'      => 'visible-md visible-lg',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <?php $mauticTemplateVars['item'] = $item; ?>
                <tr>
                    <td id="notificationId" class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
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
                    <td class="visible-md visible-lg"><?php echo $item->getMessage(); ?></td>
                    <td id="isRead" class="visible-md visible-lg"><?php echo $view['translator']->trans($item->getIsRead() ? 'mautic.core.yes' : 'mautic.core.no'); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getDateAdded()->format('Y-m-d H:i:s'); ?></td>
                    <td class="visible-md visible-lg"> 
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-nospin do-not-close" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.notifications.clear'); ?>"><i class="fa fa-times do-not-close"></i></a>
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
                'menuLinkId' => 'mautic_user_notification_index',
                'baseUrl'    => $view['router']->path('mautic_user_notification_index'),
                'sessionVar' => 'notification',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'mautic.campaign.noresults.tip']); ?>
<?php endif; ?>
