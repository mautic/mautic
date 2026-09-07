<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle\Bar;
use Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle\Baz;
use Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle\Foo;
use Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle\NamedArgumentService;
use Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle\NeedsInterface;
use Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle\TwoArg;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // flagged: the single argument only repeats the autowired constructor type
    $services->set(Foo::class)
        ->args([service(Bar::class)]);

    // flagged: every argument repeats its autowired constructor type
    $services->set(TwoArg::class)
        ->args([service(Bar::class), service(Baz::class)]);

    // flagged: the named argument repeats its autowired constructor type
    $services->set(NamedArgumentService::class)
        ->arg('$bar', service(Bar::class));

    // allowed: only the first argument is autowired, the extra one shifts if removed
    $services->set(Foo::class)
        ->args([service(Bar::class), service(Baz::class)]);

    // allowed: the constructor type hints an interface, the concrete service stands in for its binding
    $services->set(NeedsInterface::class)
        ->args([service(Bar::class)]);

    // allowed: autowiring is switched off, the argument is not redundant
    $services->set(Foo::class)
        ->autowire(false)
        ->args([service(Bar::class)]);
};
