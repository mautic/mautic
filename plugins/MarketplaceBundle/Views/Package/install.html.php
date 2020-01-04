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
/* @var \MauticPlugin\MarketplaceBundle\DTO\Version $version */
$view['slots']->set('headerTitle', $view->escape($packageDetail->getHumanPackageName()));
$view->extend('MauticCoreBundle:Default:content.html.php');
echo $view['assets']->includeScript('plugins/MarketplaceBundle/Assets/js/marketplace.js');

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'customButtons' => [
                [
                    'attr' => [
                        'data-toggle' => 'download',
                        'onClick'     => "Marketplace.startInstall(this, '{$packageDetail->getName()}')",
                    ],
                    'btnText'   => $view['translator']->trans('mautic.core.theme.install'),
                    'iconClass' => 'fa fa-download',
                ],
                [
                    'attr' => [
                        'href' => $view['router']->path(
                            RouteProvider::ROUTE_DETAIL,
                            [
                                'vendor'  => $view->escape($packageDetail->getVendorName()),
                                'package' => $view->escape($packageDetail->getPackageName()),
                            ]
                        ),
                    ],
                    'btnText'   => $view['translator']->trans('mautic.core.form.close'),
                    'iconClass' => 'fa fa-remove',
                ],
            ],
        ]
    )
);

// @todo make the stability configurable
// @todo make the version configurable

?>
<div class="col-md-12">
    <div class="alert alert-warning">
        <?php echo $view['translator']->trans(
            'marketplace.cli.suggestion',
            [
                '%maxExecutionTime%'          => $maxExecutionTime,
                '%composerRuntimeEstimation%' => $version->estimateComposerRuntime(),
            ]
        ); ?>
    </div>
    <div class="alert alert-success">
        <h4><?php echo $view['translator']->trans('marketplace.cli.installation.heading'); ?></h4>
        <p><?php echo $view['translator']->trans('marketplace.cli.installation.description'); ?></p>
        <p><code><?php echo MAUTIC_ROOT_DIR.'/bin/console mautic:marketplace:install '.$packageDetail->getName(); ?></code></p>
    </div>
    <pre id="process-details"></pre>
</div>
