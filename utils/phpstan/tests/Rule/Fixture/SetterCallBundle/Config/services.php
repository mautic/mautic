<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Utils\PHPStan\Tests\Rule\Fixture\SetterCallBundle\Repository;
use Utils\PHPStan\Tests\Rule\Fixture\SetterCallBundle\SomeService;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // flagged: a setter wired by hand on a set() service
    $services->set(SomeService::class)
        ->call('setRepository', [service(Repository::class)]);

    // flagged: a setter wired by hand on a get() service
    $services->get(Repository::class)
        ->call('setUniqueIdentifiersOperator', ['%mautic.contact_unique_identifiers_operator%']);

    // allowed: call() to a non-setter method the container cannot infer
    $services->set(SomeService::class)
        ->call('configure', [service(Repository::class)]);
};
