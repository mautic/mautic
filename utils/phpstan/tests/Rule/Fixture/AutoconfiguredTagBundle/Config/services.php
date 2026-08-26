<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle\AliasedValidator;
use Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle\PlainService;
use Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle\SomeSubscriber;
use Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle\SomeValidator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(SomeSubscriber::class)->tag('kernel.event_subscriber');
    $services->set(SomeValidator::class)->tag('validator.constraint_validator');
    $services->set(AliasedValidator::class)->tag('validator.constraint_validator', ['alias' => 'aliased_validator']);
    $services->set(PlainService::class)->tag('mautic.custom_tag');
    $services->get(SomeValidator::class)->tag('validator.constraint_validator');

    $services->set(SomeSubscriber::class)
        ->arg('$name', 'value')
        ->tag('kernel.event_subscriber');
};
