<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        //'Controller/*.php', // Enabling this will require to refactor all controllers to use DI.
    ];

    $services->load('Mautic\\UserBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\UserBundle\\Controller\\Api\\', '../Controller/Api')
        ->tag('controller.service_arguments');

    $services->load('Mautic\\UserBundle\\Entity\\', '../Entity/*Repository.php');

    $services->alias(\Mautic\UserBundle\Entity\UserTokenRepositoryInterface::class, \Mautic\UserBundle\Entity\UserTokenRepository::class);
};
