<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Utils\PHPStan\Tests\Rule\Fixture\AliasBundle\UnusedAliasHelper;
use Utils\PHPStan\Tests\Rule\Fixture\AliasBundle\UsedAliasHelper;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set('mautic.alias.used_helper', UsedAliasHelper::class);
    $services->alias(UsedAliasHelper::class, 'mautic.alias.used_helper');
    $services->alias('mautic.alias.legacy_used_helper', 'mautic.alias.used_helper');

    $services->alias('mautic.alias.integration.some_crm', 'mautic.alias.used_helper');

    $services->alias('mautic.alias.model.never_asked_for', 'mautic.alias.used_helper');

    $services->set('mautic.alias.unused_helper', UnusedAliasHelper::class);
    $services->alias(UnusedAliasHelper::class, 'mautic.alias.unused_helper');
    $services->alias('mautic.alias.legacy_unused_helper', 'mautic.alias.unused_helper');
};
