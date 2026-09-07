<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Utils\PHPStan\Tests\Rule\Fixture\ClassServiceReferenceBundle\DependentService;
use Utils\PHPStan\Tests\Rule\Fixture\ClassServiceReferenceBundle\SomeHelper;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set(SomeHelper::class);
    $services->alias('mautic.some.helper', SomeHelper::class);

    // flagged: the string id names the very class the alias points at
    $services->set('mautic.dependent.by_string', DependentService::class)
        ->args([service('mautic.some.helper')]);

    // allowed: already referenced by the class name
    $services->set('mautic.dependent.by_class', DependentService::class)
        ->args([service(SomeHelper::class)]);

    // allowed: the string alias points at another string id, not a class
    $services->alias('mautic.legacy.helper', 'mautic.some.helper');
    $services->set('mautic.dependent.by_legacy', DependentService::class)
        ->args([service('mautic.legacy.helper')]);
};
