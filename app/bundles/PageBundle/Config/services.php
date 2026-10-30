<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Security\Permissions\PagePermissions;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
    ];

    $services->load('Mautic\\PageBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\PageBundle\\Entity\\', '../Entity/*Repository.php');

    $services->get(PageModel::class)->call('setCatInUrl', ['%mautic.cat_in_page_url%']);
    $services->set(PagePermissions::class);
};
