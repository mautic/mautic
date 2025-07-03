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

    $excludes = [
    ];

    $services->load('MauticPlugin\\MauticFocusBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\MauticFocusBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->alias('mautic.focus.model.focus', MauticPlugin\MauticFocusBundle\Model\FocusModel::class);

    $services->set(MauticPlugin\MauticFocusBundle\Form\Type\FocusType::class)
        ->arg('$security', service(Mautic\CoreBundle\Security\Permissions\CorePermissions::class))
        ->arg('$coreParametersHelper', service(Mautic\CoreBundle\Helper\CoreParametersHelper::class))
        ->arg('$router', service('router'))
        ->autowire()
        ->autoconfigure()
        ->public();
};
