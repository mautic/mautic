<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Utils\PHPStan\Tests\Rule\Fixture\ServiceNameBundle\ModelNameService;
use Utils\PHPStan\Tests\Rule\Fixture\ServiceNameBundle\UnusedNameService;
use Utils\PHPStan\Tests\Rule\Fixture\ServiceNameBundle\UsedNameService;

return function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set('some.parameter.name', ModelNameService::class);

    $services = $configurator->services();

    $services->set('mautic.name.used_service', UsedNameService::class);
    $services->set('mautic.name.model.some_model', ModelNameService::class);
    $services->set('mautic.name.unused_service', UnusedNameService::class)
        ->arg('$usedService', service('mautic.name.used_service'));
};
