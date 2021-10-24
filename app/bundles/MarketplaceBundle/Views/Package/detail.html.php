<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\DTO\PackageDetail;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\RouteProvider;

/** @var PackageDetail $packageDetail */
$packageDetail = $packageDetail;

$view['slots']->set('headerTitle', $view->escape($packageDetail->packageBase->getHumanPackageName()));
$view->extend('MauticCoreBundle:Default:content.html.php');

$buttons = [
    [
        'attr' => [
            'href' => $view['router']->path(RouteProvider::ROUTE_LIST),
        ],
        'btnText'   => $view['translator']->trans('mautic.core.form.close'),
        'iconClass' => 'fa fa-remove',
    ],
];

// @todo make the stability configurable
// @todo make the version configurable
try {
    $latestVersion = $packageDetail->versions->findLatestVersionPackage();
} catch (\Throwable $e) {
    $latestVersionException = $e;
}

if (isset($latestVersion)) {
    $buttons[] = [
        'attr' => [
            'href'   => $latestVersion->issues,
            'target' => '_blank',
            'rel'    => 'noopener noreferrer',
        ],
        'btnText'   => $view['translator']->trans('marketplace.package.issue.tracker'),
        'iconClass' => 'fa fa-question',
    ];
}

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

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        ['customButtons' => $buttons]
    )
);
?>

<div class="col-md-9">
    <?php if ($packageDetail->packageBase->description) : ?>
    <div class="bg-auto">
        <div class="pr-md pl-md pt-lg pb-lg">
            <div class="box-layout">
                <div class="col-xs-10">
                    <div class="text-muted"><?php echo $view->escape($packageDetail->packageBase->description); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="panel">
    <div class="panel-heading">
        <div class="panel-title"><?php echo $view['translator']->trans('Latest Stable Version'); ?></div>
    </div>
    <table class="table table-bordered table-striped mb-0">
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.version'); ?></th>
            <td>
                <?php if (!empty($latestVersionException)) : ?>
                    <div class="text-danger">
                        <?php echo $view->escape($latestVersionException->getMessage()); ?>
                    </div>
                <?php else : ?>
                    <a href="<?php echo $view->escape($packageDetail->packageBase->repository); ?>/releases/tag/<?php echo $view->escape($latestVersion->version); ?>" target="_blank" rel="noopener noreferrer" >
                        <strong><?php echo $view->escape($latestVersion->version); ?></strong>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php if (!empty($latestVersion)) : ?>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.version.release.date'); ?></th>
            <td title="<?php echo $view['date']->toText($latestVersion->time); ?>">
                <?php echo $view['date']->toDate($latestVersion->time); ?>
            </td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.license'); ?></th>
            <td><?php echo $view->escape(implode(', ', $latestVersion->license)); ?></td>
        </tr>
        <?php if ($latestVersion->homepage) : ?>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.homepage'); ?></th>
            <td><?php echo $view->escape($latestVersion->homepage); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>
                <?php echo $view['translator']->trans('marketplace.package.required.packages'); ?>
                (<?php echo count($latestVersion->require); ?>)
            </th>
            <td><?php echo $view->escape(implode(', ', array_keys($latestVersion->require))); ?></td>
        </tr>
        <?php endif; ?>
    </table>
    </div>

    <div class="panel">
    <div class="panel-heading">
        <div class="panel-title"><?php echo $view['translator']->trans('marketplace.package.all.versions'); ?></div>
    </div>
    <table class="table table-bordered table-striped mb-0">
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.version'); ?></th>
            <th><?php echo $view['translator']->trans('marketplace.package.version.release.date'); ?></th>
        </tr>
        <?php foreach ($packageDetail->versions->sortByLatest() as $version) : ?>
        <tr>
            <td>
                <a href="<?php echo $view->escape($packageDetail->packageBase->repository); ?>/releases/tag/<?php echo $view->escape($version->version); ?>" target="_blank" rel="noopener noreferrer" >
                    <?php echo $view->escape($version->version); ?>
                </a>
            </td>
            <td title="<?php echo $view['date']->toText($version->time); ?>">
                <?php echo $view['date']->toDate($version->time); ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<div class="col-md-3 panel pb-lg">
    <h3 class="pt-lg pb-lg pl-sm"><?php echo $view['translator']->trans('marketplace.package.maintainers'); ?></h3>
    <?php foreach ($packageDetail->maintainers as $maintainer) : ?>
        <div class="box-layout">
            <div class="col-xs-3 va-m">
                <div class="panel-body">
                    <span class="img-wrapper img-rounded">
                        <img class="img" src="<?php echo $view->escape($maintainer->avatar); ?>">
                    </span>
                </div>
            </div>
            <div class="col-xs-9 va-t">
                <div class="panel-body">
                    <h4 class="fw-sb mb-xs ellipsis">
                        <?php echo $view->escape(ucfirst($maintainer->name)); ?>
                    </h4>
                    <a href="https://packagist.org/packages/<?php echo $view->escape($maintainer->name); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo $view['translator']->trans('marketplace.other.packages', ['%name%' => $maintainer->name]); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <h3 class="pt-lg pb-lg pl-sm"><?php echo $view['translator']->trans('marketplace.package.github.info'); ?></h3>
    <table class="table mb-0">
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.repository'); ?></th>
            <td>
                <a href="<?php echo $view->escape($packageDetail->packageBase->repository); ?>" target="_blank" rel="noopener noreferrer" >
                    <?php echo $view->escape($packageDetail->packageBase->name); ?>
                </a>
            </td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.stars'); ?></th>
            <td><?php echo $view->escape($packageDetail->githubInfo->stars); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.watchers'); ?></th>
            <td><?php echo $view->escape($packageDetail->githubInfo->watchers); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.forks'); ?></th>
            <td><?php echo $view->escape($packageDetail->githubInfo->forks); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.open.issues'); ?></th>
            <td><?php echo $view->escape($packageDetail->githubInfo->openIssues); ?></td>
        </tr>
    </table>

    <h3 class="pt-lg pb-lg pl-sm"><?php echo $view['translator']->trans('marketplace.package.packagist.info'); ?></h3>
    <table class="table mb-0">
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.repository'); ?></th>
            <td>
                <a href="<?php echo $view->escape($packageDetail->packageBase->url); ?>" target="_blank" rel="noopener noreferrer" >
                    <?php echo $view->escape($packageDetail->packageBase->name); ?>
                </a>
            </td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.total.downloads'); ?></th>
            <td><?php echo $view->escape($packageDetail->packageBase->downloads); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.monthly.downloads'); ?></th>
            <td><?php echo $view->escape($packageDetail->monthlyDownloads); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.daily.downloads'); ?></th>
            <td><?php echo $view->escape($packageDetail->dailyDownloads); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.create.date'); ?></th>
            <td title="<?php echo $view['date']->toText($packageDetail->time); ?>">
                <?php echo $view['date']->toDate($packageDetail->time); ?>
            </td>
        </tr>
    </table>
</div>


