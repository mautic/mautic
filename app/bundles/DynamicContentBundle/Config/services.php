<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(Mautic\DynamicContentBundle\Form\Type\DwcEntryFiltersType::class)
        ->call('setConnection', [service('database_connection')]);

    $services->load('Mautic\\DynamicContentBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

    $services->load('Mautic\\DynamicContentBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->alias('mautic.dynamicContent.model.dynamicContent', Mautic\DynamicContentBundle\Model\DynamicContentModel::class);
};
