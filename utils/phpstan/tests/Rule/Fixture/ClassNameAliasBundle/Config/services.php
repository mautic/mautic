<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Utils\PHPStan\Tests\Rule\Fixture\ClassNameAliasBundle\SomeHelper;
use Utils\PHPStan\Tests\Rule\Fixture\ClassNameAliasBundle\SomeHelperInterface;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set('mautic.alias.some_helper', SomeHelper::class);
    $services->alias(SomeHelper::class, 'mautic.alias.some_helper');

    // the other way around is the shape to keep
    $services->alias('mautic.alias.legacy_some_helper', SomeHelper::class);

    // an interface to its implementation is plain autowiring
    $services->alias(SomeHelperInterface::class, SomeHelper::class);

    // a bridge to a service of another class is no duplicate
    $services->set('mautic.alias.http_client', SomeHelper::class);
    $services->alias(SomeHelperInterface::class, 'mautic.alias.http_client');

    // a vendor service id is only reachable by such an alias
    $services->alias(Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface::class, 'argument_resolver');
    $services->alias(Oneup\UploaderBundle\Templating\Helper\UploaderHelper::class, 'oneup_uploader.templating.uploader_helper');
};
