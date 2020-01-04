<?php declare(strict_types=1);

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use MauticPlugin\MarketplaceBundle\DTO\Version;
use MauticPlugin\MarketplaceBundle\Service\RouteProvider;

/* @var \MauticPlugin\MarketplaceBundle\DTO\PackageDetail $packageDetail */
$view['slots']->set('headerTitle', $view->escape($packageDetail->getHumanPackageName()));
$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'customButtons' => [
                [
                    'attr' => [
                        'href' => $view['router']->path(RouteProvider::ROUTE_LIST),
                    ],
                    'btnText'   => $view['translator']->trans('mautic.core.form.close'),
                    'iconClass' => 'fa fa-remove',
                ],
                [
                    'attr' => [
                        'href' => $view['router']->path(
                            RouteProvider::ROUTE_INSTALL,
                            [
                                'vendor'  => $view->escape($packageDetail->getVendorName()),
                                'package' => $view->escape($packageDetail->getPackageName()),
                            ]
                        ),
                    ],
                    'btnText'   => $view['translator']->trans('mautic.core.theme.install'),
                    'iconClass' => 'fa fa-download',
                ],
            ],
        ]
    )
);

// @todo make the stability configurable
// @todo make the version configurable
try {
    $latestVersion = $packageDetail->getVersions()->findLatestVersionPackage(Version::STABILITY_BETA);
} catch (\Throwable $e) {
    $latestVersionException = $e;
}
?>
<div class="col-md-12">
    <table class="table table-bordered table-striped mb-0">
        <tr>
            <th><?php echo $view['translator']->trans('mautic.core.name'); ?></th>
            <td>
                <a href="<?php echo $view->escape($packageDetail->getRepository()); ?>" target="_blank" rel="noopener noreferrer" >
                    <?php echo $view->escape($packageDetail->getName()); ?>
                </a>
            </td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('mautic.core.description'); ?></th>
            <td><?php echo $view->escape($packageDetail->getDescription()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.stars'); ?></th>
            <td><?php echo $view->escape($packageDetail->getGithubStars()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.watchers'); ?></th>
            <td><?php echo $view->escape($packageDetail->getGithubWatchers()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.forks'); ?></th>
            <td><?php echo $view->escape($packageDetail->getGithubForks()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.open.issues'); ?></th>
            <td><?php echo $view->escape($packageDetail->getGithubOpenIssues()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.dependents'); ?></th>
            <td><?php echo $view->escape($packageDetail->getDependents()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.suggesters'); ?></th>
            <td><?php echo $view->escape($packageDetail->getSuggesters()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.total.downloads'); ?></th>
            <td><?php echo $view->escape($packageDetail->getDownloads()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.monthly.downloads'); ?></th>
            <td><?php echo $view->escape($packageDetail->getMonthlyDownloads()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.daily.downloads'); ?></th>
            <td><?php echo $view->escape($packageDetail->getDailyDownloads()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.create.date'); ?></th>
            <td><?php echo $view['date']->toFull($view->escape($packageDetail->getTime())); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.maintainers'); ?></th>
            <td>
                <?php foreach ($packageDetail->getMaintainers() as $maintainer) : ?>
                    <?php if ($maintainer->getAvatar()) : ?>
                        <img src="<?php echo $view->escape($maintainer->getAvatar()); ?>" class="img-rounded" style="width: 27px;"/>
                    <?php endif; ?>
                    <?php echo $view->escape($maintainer->getName()); ?>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.latest.version'); ?></th>
            <td>
                <?php if (!empty($latestVersionException)) : ?>
                    <div class="text-danger">
                        <?php echo $view->escape($latestVersionException->getMessage()); ?>
                    </div>
                <?php else : ?>
                    <?php echo $view->escape($latestVersion->getVersion()); ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php if (!empty($latestVersion)) : ?>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.license'); ?></th>
            <td><?php echo $view->escape(implode(', ', $latestVersion->getLicense())); ?></td>
        </tr>
        <?php if ($latestVersion->getHomepage()) : ?>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.homepage'); ?></th>
            <td><?php echo $view->escape($latestVersion->getHomepage()); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.issue.tracker'); ?></th>
            <td>
                <a href="<?php echo $view->escape($latestVersion->getIssues()); ?>" target="_blank" rel="noopener noreferrer" >
                    <?php echo $view->escape($latestVersion->getIssues()); ?>
                </a>
            </td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.latest.version.date'); ?></th>
            <td><?php echo $view['date']->toFull($view->escape($latestVersion->getTime())); ?></td>
        </tr>
        <tr>
            <th>
                <?php echo $view['translator']->trans('marketplace.package.required.packages'); ?>
                (<?php echo count($latestVersion->getRequire()); ?>)
            </th>
            <td><?php echo $view->escape(implode(', ', array_keys($latestVersion->getRequire()))); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.keywords'); ?></th>
            <td><?php echo $view->escape(implode(', ', $latestVersion->getKeywords())); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>
