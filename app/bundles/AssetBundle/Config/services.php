<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Controller/UploadController.php',
    ];

    $services->load('Mautic\\AssetBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\AssetBundle\\Entity\\', '../Entity/*Repository.php');
    $services->alias('mautic.asset.helper.token', \Mautic\AssetBundle\Helper\TokenHelper::class);
    $services->alias('mautic.asset.model.asset', \Mautic\AssetBundle\Model\AssetModel::class);
    $services->alias(\Oneup\UploaderBundle\Templating\Helper\UploaderHelper::class, 'oneup_uploader.templating.uploader_helper');
};
