<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle\SomeSubscriber;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->public();

    $services->set(SomeSubscriber::class)->tag('kernel.event_subscriber');
};
