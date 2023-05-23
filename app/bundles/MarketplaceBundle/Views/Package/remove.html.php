<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\DTO\PackageDetail;
use Mautic\MarketplaceBundle\Service\RouteProvider;

/** @var PackageDetail $packageDetail */
$packageDetail = $packageDetail;
?>
<div class="text-center" id="marketplace-removal-in-progress">
    <p><?php echo $view['translator']->trans(
        'marketplace.package.remove.html.in.progress',
        ['%packagename%' => $view->escape($packageDetail->packageBase->getHumanPackageName())]
        ); ?></p>
    <div class="spinner">
        <i class="fa fa-spin fa-spinner"></i>
    </div>
</div>
<div style="display: none" class="text-center" id="marketplace-removal-failed">
    <p><?php echo $view['translator']->trans(
        'marketplace.package.remove.html.failed',
        ['%packagename%' => $view->escape($packageDetail->packageBase->getHumanPackageName())]
        ); ?></p>
    <textarea class="form-control" readonly id="marketplace-removal-failed-details"></textarea>
</div>
<div style="display: none" class="text-center" id="marketplace-removal-success">
    <p><?php echo $view['translator']->trans(
        'marketplace.package.remove.html.success',
        ['%packagename%' => $view->escape($packageDetail->packageBase->getHumanPackageName())]
        ); ?></p>
    <p><a class="btn btn-primary" href="<?php echo $view['router']->path(RouteProvider::ROUTE_LIST); ?>"><?php echo $view['translator']->trans(
        'marketplace.package.remove.html.success.continue'); ?></a></p>
</div>

<script>
    const removePackageResetView = () => {
        mQuery('#marketplace-removal-in-progress').show();
        mQuery('#marketplace-removal-success').hide();
        mQuery('#marketplace-removal-failed').hide();
    }

    removePackageResetView();

    Mautic.Marketplace.removePackage(
        '<?php echo htmlspecialchars($packageDetail->packageBase->getVendorName(), ENT_QUOTES); ?>',
        '<?php echo htmlspecialchars($packageDetail->packageBase->getPackageName(), ENT_QUOTES); ?>',
        (response) => {
            if (response.success) {
                mQuery('#marketplace-removal-in-progress').hide();
                mQuery('#marketplace-removal-success').show();
            } else if (response.redirect) {
                window.location = response.redirect;
            }
        },
        (request, textStatus, errorThrown) => {
            let error;

            try {
                const res = JSON.parse(request.responseText);
                if (res.error) {
                    error = res.error;
                } else {
                    error = res.errors[0].message ?? 'Unknown error';
                }
            } catch (e) {
                error = 'An unknown error occurred. Please check the logs for more details.';
                console.error(request.responseText);
                console.error(e);
            }
            
            mQuery('#marketplace-removal-in-progress').hide();
            mQuery('#marketplace-removal-failed').show();
            mQuery('#marketplace-removal-failed-details').text(error);
        }
    );
</script>