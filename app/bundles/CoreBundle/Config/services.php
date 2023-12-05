<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
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
        'Twig/Helper/ThemeHelper.php',
        'Translation/TranslatorLoader.php',
        'Helper/Dsn/Dsn.php',
    ];

    $services->load('Mautic\\CoreBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CoreBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set('mautic.http.client', \GuzzleHttp\Client::class)->autowire();

    $services->alias(\GuzzleHttp\Client::class, 'mautic.http.client');
    $services->alias(\Psr\Http\Client\ClientInterface::class, 'mautic.http.client');
    $services->alias('mautic.factory', \Mautic\CoreBundle\Factory\MauticFactory::class);
    $services->alias(\Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface::class, 'argument_resolver');

    $services->alias(\Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface::class, \Mautic\CoreBundle\Doctrine\Provider\VersionProvider::class);
    $services->alias('mautic.model.factory', \Mautic\CoreBundle\Factory\ModelFactory::class);
    $services->alias('twig.helper.assets', \Mautic\CoreBundle\Twig\Helper\AssetsHelper::class);
    $services->alias('transifex.factory', \Mautic\CoreBundle\Factory\TransifexFactory::class);
    $services->alias('mautic.helper.language', \Mautic\CoreBundle\Helper\LanguageHelper::class);
    $services->alias('mautic.helper.email.address', \Mautic\CoreBundle\Helper\EmailAddressHelper::class);
    $services->alias('mautic.helper.assetgeneration', \Mautic\CoreBundle\Helper\AssetGenerationHelper::class);
    $services->alias('twig.helper.slots', \Mautic\CoreBundle\Twig\Helper\SlotsHelper::class);

    $services->get(\Mautic\CoreBundle\Twig\Helper\AssetsHelper::class)->tag('twig.helper', ['alias' => 'assets']);

    $services->get(\Mautic\CoreBundle\Model\NotificationModel::class)->call('setDisableUpdates', ['%mautic.security.disableUpdates%']);
    $services->alias('mautic.core.model.auditlog', \Mautic\CoreBundle\Model\AuditLogModel::class);
    $services->alias('mautic.core.model.notification', \Mautic\CoreBundle\Model\NotificationModel::class);
    $services->alias('mautic.core.model.form', \Mautic\CoreBundle\Model\FormModel::class);
};
