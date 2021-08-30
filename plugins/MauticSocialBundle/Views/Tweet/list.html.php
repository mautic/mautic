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
    $view->extend('MauticSocialBundle:Tweet:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered tweet-list" id="tweetTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#tweetTable',
                        'langVar'         => 'mautic.social.tweets',
                        'routeBase'       => 'mautic_tweet',
                        'templateButtons' => [
                            'delete' => $view['security']->isGranted('mauticSocial:tweet:delete'),
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'social.tweet',
                        'orderBy'    => 'e.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-tweet-name',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'social.tweet',
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
                                    'edit'   => $view['security']->isGranted('mauticSocial:tweet:edit'),
                                    'delete' => $view['security']->isGranted('mauticSocial:tweet:delete'),
                                ],
                                'routeBase'  => 'mautic_tweet',
                                'langVar'    => 'mautic.integration.Twitter',
                                'nameGetter' => 'getName',
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
                                    'model' => 'social.tweet',
                                ]
                            ); ?>
                            <a href="<?php echo $view['router']->path(
                                'mautic_tweet_action',
                                ['objectAction' => 'edit', 'objectId' => $item->getId()]
                            ); ?>"
                               data-toggle="ajax">
                                <?php echo $item->getName(); ?>
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
                'menuLinkId' => 'mautic_tweet_index',
                'baseUrl'    => $view['router']->path('mautic_tweet_index'),
                'sessionVar' => 'social.tweet',
                'routeBase'  => 'tweet',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'mautic.mautic.social.tweet.noresults.tip']); ?>
<?php endif; ?>

