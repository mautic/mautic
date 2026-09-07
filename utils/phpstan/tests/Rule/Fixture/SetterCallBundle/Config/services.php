<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Utils\PHPStan\Tests\Rule\Fixture\SetterCallBundle\Repository;
use Utils\PHPStan\Tests\Rule\Fixture\SetterCallBundle\SomeService;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // flagged: a setter fed a service() on a set() service
    $services->set(SomeService::class)
        ->call('setRepository', [service(Repository::class)]);

    // flagged: a setter fed a service() on a get() service
    $services->get(Repository::class)
        ->call('setRepository', [service(Repository::class)]);

    // allowed: the setter is fed a container parameter, not a service
    $services->set(SomeService::class)
        ->call('setUniqueIdentifiersOperator', ['%mautic.contact_unique_identifiers_operator%']);

    // allowed: call() to a non-setter method
    $services->set(SomeService::class)
        ->call('configure', [service(Repository::class)]);
};
