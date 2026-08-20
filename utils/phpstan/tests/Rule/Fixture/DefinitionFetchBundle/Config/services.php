<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Utils\PHPStan\Tests\Rule\Fixture\DefinitionFetchBundle\AliasedHelper;
use Utils\PHPStan\Tests\Rule\Fixture\DefinitionFetchBundle\StringHelper;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // string-primary: a class is known for the id
    $services->set('mautic.string.helper', StringHelper::class);

    // class-primary with a string alias pointing at the class
    $services->set(AliasedHelper::class);
    $services->alias('mautic.aliased.helper', AliasedHelper::class);
};
