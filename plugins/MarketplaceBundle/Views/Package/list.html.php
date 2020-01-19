<?php declare(strict_types=1);

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use MauticPlugin\MarketplaceBundle\Collection\PackageCollection;
use MauticPlugin\MarketplaceBundle\Service\RouteProvider;

if ('index' === $tmpl) {
    $view->extend('MarketplaceBundle:Package:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="marketplace-packages-table">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'  => 'true',
                        'target'    => '#packages-table',
                        'langVar'   => 'marketplace.package',
                        'routeBase' => 'marketplace',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'marketplace.package',
                        'orderBy'    => 'name',
                        'text'       => 'mautic.core.name',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'marketplace.package',
                        'orderBy'    => 'vendor',
                        'text'       => 'marketplace.vendor',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'marketplace.package',
                        'orderBy'    => 'downloads',
                        'text'       => 'marketplace.downloads',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'marketplace.package',
                        'orderBy'    => 'favers',
                        'text'       => 'marketplace.favers',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php /** @var PackageCollection $items */ ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'customButtons'   => [
                                    [
                                        'attr' => [
                                            'href' => $view['router']->path(
                                                RouteProvider::ROUTE_INSTALL,
                                                [
                                                    'vendor'  => $view->escape($item->getVendorName()),
                                                    'package' => $view->escape($item->getPackageName()),
                                                ]
                                            ),
                                        ],
                                        'btnText'   => $view['translator']->trans('mautic.core.theme.install'),
                                        'iconClass' => 'fa fa-download',
                                    ],
                                ],
                            ]
                        ); ?>
                    </td>
                    <td>
                        <div>
                            <a href="<?php echo $view['router']->path(
                                    RouteProvider::ROUTE_DETAIL,
                                    [
                                        'vendor'  => $view->escape($item->getVendorName()),
                                        'package' => $view->escape($item->getPackageName()),
                                    ]
                                ); ?>">
                                <?php echo $view->escape($item->getHumanPackageName()); ?>
                            </a>
                        </div>
                        <?php if ($item->getDescription()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $view->escape($item->getDescription()); ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $view->escape($item->getVendorName()); ?></td>
                    <td><?php echo $view->escape($item->getDownloads()); ?></td>
                    <td><?php echo $view->escape($item->getFavers()); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php
        echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            [
                'totalItems' => $count,
                'page'       => $page,
                'limit'      => $limit,
                'baseUrl'    => $view['router']->path(RouteProvider::ROUTE_LIST),
                'sessionVar' => 'marketplace.package',
                'routeBase'  => RouteProvider::ROUTE_LIST,
            ]
        );
        ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'custom.object.noresults.tip']); ?>
<?php endif; ?>
