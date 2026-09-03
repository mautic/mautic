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
    $services->set('mautic.helper.dynamicContent', Mautic\DynamicContentBundle\Helper\DynamicContentHelper::class);
    $services->alias(Mautic\DynamicContentBundle\Helper\DynamicContentHelper::class, 'mautic.helper.dynamicContent');
    $services->alias('mautic.dynamicContent.model.dynamicContent', Mautic\DynamicContentBundle\Model\DynamicContentModel::class);
    $services->alias('mautic.dynamicContent.repository.stat', Mautic\DynamicContentBundle\Entity\StatRepository::class);
    $services->alias('mautic.form.type.dwc_entry_filters', Mautic\DynamicContentBundle\Form\Type\DwcEntryFiltersType::class)
        ->deprecate('mautic/mautic', '7.2', 'The "%alias_id%" service alias is deprecated. Use the "'.Mautic\DynamicContentBundle\Form\Type\DwcEntryFiltersType::class.'" service instead.');
};
