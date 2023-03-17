<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Doctrine',
        'Model/IteratorExportDataModel.php',
        'Form/EventListener/FormExitSubscriber.php',
        'Release',
        'Helper/Chart',
        'Helper/CommandResponse.php',
        'Helper/Language/Installer.php',
        'Helper/PageHelper.php',
        'Helper/Tree/IntNode.php',
        'Helper/Update/Github/Release.php',
        'Helper/Update/PreUpdateChecks',
        'Predis/Replication/StrategyConfig.php',
        'Predis/Replication/MasterOnlyStrategy.php',
        'Session/Storage/Handler/RedisSentinelSessionHandler.php',
        'Templating/Engine/PhpEngine.php', // Will be removed in M5
        'Templating/Helper/FormHelper.php',
        'Templating/Helper/ThemeHelper.php',
        'Translation/TranslatorLoader.php',
        'Helper/Dsn/Dsn.php',
        'Helper/Dsn/Dsn/DsnGenerator.php',
    ];

    $services->load('Mautic\\CoreBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CoreBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set('mautic.http.client', \GuzzleHttp\Client::class)->autowire();

    $services->alias(\GuzzleHttp\Client::class, 'mautic.http.client');
    $services->alias(\Psr\Http\Client\ClientInterface::class, 'mautic.http.client');

    $services->alias(\Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface::class, \Mautic\CoreBundle\Doctrine\Provider\VersionProvider::class);
    $services->alias('mautic.model.factory', \Mautic\CoreBundle\Factory\ModelFactory::class);
    $services->alias('templating.helper.assets', \Mautic\CoreBundle\Templating\Helper\AssetsHelper::class);
    $services->alias('transifex.factory', \Mautic\CoreBundle\Factory\TransifexFactory::class);
    $services->alias('mautic.helper.language', \Mautic\CoreBundle\Helper\LanguageHelper::class);
    $services->alias('mautic.helper.email.address', \Mautic\CoreBundle\Helper\EmailAddressHelper::class);

    $services->get(\Mautic\CoreBundle\Templating\Helper\AssetsHelper::class)->tag('templating.helper', ['alias' => 'assets']);
};
