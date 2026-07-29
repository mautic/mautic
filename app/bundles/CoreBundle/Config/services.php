<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Twig\Extra\String\StringExtension;

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
        'Form/DataTransformer',
        'Helper/CommandResponse.php',
        'Helper/Language/Installer.php',
        'Helper/PageHelper.php',
        'Helper/Tree/IntNode.php',
        'Helper/Update/Github/Release.php',
        'Helper/Update/PreUpdateChecks',
        'Predis/Replication/StrategyConfig.php',
        'Predis/Replication/MasterOnlyStrategy.php',
        'ProcessSignal/Exception',
        'ProcessSignal/ProcessSignalState.php',
        'Session/Storage/Handler/RedisSentinelSessionHandler.php',
        'Twig/Helper/ThemeHelper.php',
        'Translation/TranslatorLoader.php',
        'Helper/Dsn/Dsn.php',
        'Cache/ResultCacheOptions.php',
    ];

    $services->load('Mautic\\CoreBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CoreBundle\\Entity\\', '../Entity/*Repository.php');

    $services->alias('mautic.helper.file_uploader', Mautic\CoreBundle\Helper\FileUploader::class);
    $services->alias('mautic.helper.file_path_resolver', Mautic\CoreBundle\Helper\FilePathResolver::class);
    $services->alias('mautic.helper.file_properties', Mautic\CoreBundle\Helper\FileProperties::class);
    $services->alias('mautic.core.validator.file_upload', Mautic\CoreBundle\Validator\FileUploadValidator::class);
    $services->alias('mautic.filesystem', Mautic\CoreBundle\Helper\Filesystem::class);

    /* @deprecated to be removed in Mautic 4. Use 'mautic.filesystem' instead. */
    $services->set('symfony.filesystem', Symfony\Component\Filesystem\Filesystem::class);
    $services->alias(Symfony\Component\Filesystem\Filesystem::class, 'symfony.filesystem');

    $services->set('symfony.finder', Symfony\Component\Finder\Finder::class);
    $services->alias(Symfony\Component\Finder\Finder::class, 'symfony.finder');

    $services->alias('mautic.helper.input_helper', Mautic\CoreBundle\Helper\InputHelper::class);
    $services->alias('mautic.helper.trailing_slash', Mautic\CoreBundle\Helper\TrailingSlashHelper::class);
    $services->alias('mautic.helper.url', Mautic\CoreBundle\Helper\UrlHelper::class);
    $services->alias('mautic.helper.hash', Mautic\CoreBundle\Helper\HashHelper\HashHelper::class);
    $services->alias('mautic.helper.random', Mautic\CoreBundle\Helper\RandomHelper\RandomHelper::class);
    $services->alias('mautic.helper.phone_number', Mautic\CoreBundle\Helper\PhoneNumberHelper::class);

    $services->get(Mautic\CoreBundle\EventListener\ErrorHandlingListener::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'))
        ->arg('$mainLogger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger'))
        ->arg('$debugLogger', \Symfony\Component\DependencyInjection\Loader\Configurator\expr("container.has('monolog.logger.chrome') ? container.get('monolog.logger.chrome') : null"));
    $services->alias('mautic.core.errorhandler.subscriber', Mautic\CoreBundle\EventListener\ErrorHandlingListener::class);

    $services->get(Mautic\CoreBundle\Helper\UpdateHelper::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'));
    $services->alias('mautic.helper.update', Mautic\CoreBundle\Helper\UpdateHelper::class);
    $services->alias('mautic.helper.update.release_parser', Mautic\CoreBundle\Helper\Update\Github\ReleaseParser::class);

    $services->get(Mautic\CoreBundle\Helper\ComposerHelper::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'));
    $services->alias('mautic.helper.composer', Mautic\CoreBundle\Helper\ComposerHelper::class);

    $services->get(Mautic\CoreBundle\Update\Step\DeleteCacheStep::class)->tag('mautic.update_step');
    $services->alias('mautic.update.step.delete_cache', Mautic\CoreBundle\Update\Step\DeleteCacheStep::class);

    $services->get(Mautic\CoreBundle\Update\Step\FinalizeUpdateStep::class)->tag('mautic.update_step');
    $services->alias('mautic.update.step.finalize', Mautic\CoreBundle\Update\Step\FinalizeUpdateStep::class);

    $services->get(Mautic\CoreBundle\Update\Step\InstallNewFilesStep::class)->tag('mautic.update_step');
    $services->alias('mautic.update.step.install_new_files', Mautic\CoreBundle\Update\Step\InstallNewFilesStep::class);

    $services->get(Mautic\CoreBundle\Update\Step\RemoveDeletedFilesStep::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'))
        ->tag('mautic.update_step');
    $services->alias('mautic.update.step.remove_deleted_files', Mautic\CoreBundle\Update\Step\RemoveDeletedFilesStep::class);

    $services->get(Mautic\CoreBundle\Update\Step\UpdateSchemaStep::class)->tag('mautic.update_step');
    $services->alias('mautic.update.step.update_schema', Mautic\CoreBundle\Update\Step\UpdateSchemaStep::class);

    $services->get(Mautic\CoreBundle\Update\Step\UpdateTranslationsStep::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'))
        ->tag('mautic.update_step');
    $services->alias('mautic.update.step.update_translations', Mautic\CoreBundle\Update\Step\UpdateTranslationsStep::class);

    $services->get(Mautic\CoreBundle\Update\Step\PreUpdateChecksStep::class)->tag('mautic.update_step');
    $services->alias('mautic.update.step.checks', Mautic\CoreBundle\Update\Step\PreUpdateChecksStep::class);

    $services->set(Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckPhpVersion::class)->tag('mautic.update_check');
    $services->alias('mautic.update.checks.php', Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckPhpVersion::class);

    $services->set(Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckDatabaseDriverAndVersion::class)->tag('mautic.update_check');
    $services->alias('mautic.update.checks.database', Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckDatabaseDriverAndVersion::class);

    $services->alias('mautic.core.repository.ip_address', Mautic\CoreBundle\Entity\IpAddressRepository::class);

    // Explicitly register our Twig extension with high priority
    $services->set(Mautic\CoreBundle\Twig\Extension\OverrideIncludeExtension::class)
        ->autowire()
        ->tag('twig.extension', ['priority' => 100]);

    $services->set('mautic.http.client', GuzzleHttp\Client::class)->autowire();
    $services->set(Mautic\CoreBundle\Doctrine\MigrationFactoryDecorator::class)->autowire();

    $services->set(StringExtension::class)
        ->tag('twig.extension');

    $services->alias(GuzzleHttp\Client::class, 'mautic.http.client');
    $services->alias(Psr\Http\Client\ClientInterface::class, 'mautic.http.client');
    $services->alias(Symfony\Component\DependencyInjection\ContainerInterface::class, 'service_container');
    $services->alias(Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface::class, 'argument_resolver');

    $services->alias(Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface::class, Mautic\CoreBundle\Doctrine\Provider\VersionProvider::class);
    $services->alias('mautic.model.factory', Mautic\CoreBundle\Factory\ModelFactory::class);
    $services->alias('twig.helper.assets', Mautic\CoreBundle\Twig\Helper\AssetsHelper::class);
    $services->alias('transifex.factory', Mautic\CoreBundle\Factory\TransifexFactory::class);
    $services->alias('mautic.helper.language', Mautic\CoreBundle\Helper\LanguageHelper::class);
    $services->alias('mautic.helper.email.address', Mautic\CoreBundle\Helper\EmailAddressHelper::class);
    $services->alias('mautic.helper.assetgeneration', Mautic\CoreBundle\Helper\AssetGenerationHelper::class);
    $services->alias('mautic.helper.update_checks', Mautic\CoreBundle\Helper\PreUpdateCheckHelper::class);
    $services->alias('mautic.update.step_provider', Mautic\CoreBundle\Update\StepProvider::class);

    $services->get(Mautic\CoreBundle\Twig\Helper\AssetsHelper::class)->tag('twig.helper', ['alias' => 'assets']);

    $services->get(Mautic\CoreBundle\Model\NotificationModel::class)->call('setDisableUpdates', ['%mautic.security.disableUpdates%']);
    $services->alias('mautic.core.model.auditlog', Mautic\CoreBundle\Model\AuditLogModel::class);
    $services->alias('mautic.core.model.notification', Mautic\CoreBundle\Model\NotificationModel::class);
    $services->alias('mautic.core.model.form', Mautic\CoreBundle\Model\FormModel::class);
};
