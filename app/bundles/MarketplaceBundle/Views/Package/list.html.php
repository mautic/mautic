<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\Collection\PackageCollection;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\RouteProvider;

if ('index' === $tmpl) {
    $view->extend('MarketplaceBundle:Package:index.html.php');
}

$buttons = [];

if ($view['security']->isGranted(MarketplacePermissions::CAN_INSTALL_PACKAGES)) {
    $buttons[] = [
        'attr' => [
            'data-toggle'      => 'confirmation',
            'data-message'     => $view['translator']->trans('marketplace.install.coming.soon'),
            'data-cancel-text' => $view['translator']->trans('mautic.core.close'),
        ],
        'btnText'   => $view['translator']->trans('mautic.core.theme.install'),
        'iconClass' => 'fa fa-download',
    ];
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
                        'text'       => 'mautic.core.name',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'text'       => 'marketplace.vendor',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'text'       => 'marketplace.downloads',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
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
                                'customButtons'   => $buttons,
                            ]
                        ); ?>
                    </td>
                    <td class="package-name">
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
                        <?php if ($item->description): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $view->escape($item->description); ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="vendor-name"><?php echo $view->escape($item->getVendorName()); ?></td>
                    <td class="downloads"><?php echo $view->escape($item->downloads); ?></td>
                    <td class="favers"><?php echo $view->escape($item->favers); ?></td>
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
