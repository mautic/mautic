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

    $services->load('Mautic\\UserBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

    $services->load('Mautic\\UserBundle\\Entity\\', '../Entity/*Repository.php');

    $services->alias(\Mautic\UserBundle\Entity\UserTokenRepositoryInterface::class, \Mautic\UserBundle\Entity\UserTokenRepository::class);
};
