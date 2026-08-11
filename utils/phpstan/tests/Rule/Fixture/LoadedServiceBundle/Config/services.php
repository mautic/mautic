<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Utils\PHPStan\Tests\Rule\Fixture\LoadedServiceBundle\AlreadyLoadedService;
use Utils\PHPStan\Tests\Rule\Fixture\LoadedServiceBundle\ConfiguredService;
use Utils\PHPStan\Tests\Rule\Fixture\LoadedServiceBundle\Event\ExcludedEventService;
use Utils\PHPStan\Tests\Rule\Fixture\LoadedServiceBundle\Nested\CustomExcludedService;
use Utils\PHPStan\Tests\Rule\Fixture\LoadedServiceBundle\SecondAlreadyLoadedService;
use Utils\PHPStan\Tests\Rule\Fixture\ServiceNameBundle\UsedNameService;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Nested',
    ];

    $services->load('Utils\\PHPStan\\Tests\\Rule\\Fixture\\LoadedServiceBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->set(AlreadyLoadedService::class);
    $services->set(SecondAlreadyLoadedService::class);
    $services->set(ConfiguredService::class)->tag('kernel.reset', ['method' => 'reset']);
    $services->set(ExcludedEventService::class);
    $services->set(CustomExcludedService::class);
    $services->set(UsedNameService::class);
};
