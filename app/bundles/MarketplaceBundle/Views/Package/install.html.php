<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\DTO\PackageDetail;

/** @var PackageDetail $packageDetail */
$packageDetail = $packageDetail;

try {
    $latestVersion = $packageDetail->versions->findLatestVersionPackage();
} catch (\Throwable $e) {
    $latestVersionException = $e;
}

?>
<div class="text-center">
    <p><strong><?php echo $packageDetail->packageBase->getHumanPackageName(); ?></strong> is being installed. This might take a while...</p>
    <div class="spinner">
        <i class="fa fa-spin fa-spinner"></i>
    </div>
</div>

<script>
    Mautic.Marketplace.installPackage(
        '<?php echo htmlspecialchars($packageDetail->packageBase->getVendorName(), ENT_QUOTES); ?>',
        '<?php echo htmlspecialchars($packageDetail->packageBase->getPackageName(), ENT_QUOTES); ?>',
    );
</script>