<?php

declare(strict_types=1);

use Mautic\AssetBundle\Controller\UploadController;
use Mautic\AssetBundle\Security\Permissions\AssetPermissions;
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Oneup\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set('oneup_uploader.controller.dropzone.class', UploadController::class);

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

    $services->set(AssetPermissions::class)->tag('mautic.permissions');
    $services->alias(UploaderHelper::class, 'oneup_uploader.templating.uploader_helper');
};
