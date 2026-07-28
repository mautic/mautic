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
        ->public()
        ->bind('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'));

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

    $services->set(Mautic\CoreBundle\Doctrine\Loader\MauticFixturesLoader::class)
        ->arg('$fixturesLoader', \Symfony\Component\DependencyInjection\Loader\Configurator\service('doctrine.fixtures.loader'));

    $services->load('Mautic\\CoreBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CoreBundle\\Entity\\', '../Entity/*Repository.php');
    $services->set('mautic.core.service.flashbag', Mautic\CoreBundle\Service\FlashBag::class);
    $services->set('mautic.core.service.bulk_notification', Mautic\CoreBundle\Service\BulkNotification::class);
    $services->set('mautic.core.service.log_processor', Mautic\CoreBundle\Monolog\LogProcessor::class)->tag('monolog.processor');
    $services->set('mautic.helper.app_version', Mautic\CoreBundle\Helper\AppVersion::class);
    $services->set('mautic.helper.ip_lookup', Mautic\CoreBundle\Helper\IpLookupHelper::class);
    $services->set('mautic.helper.user', Mautic\CoreBundle\Helper\UserHelper::class);
    $services->set('mautic.helper.phone_number', Mautic\CoreBundle\Helper\PhoneNumberHelper::class);
    $services->set('mautic.helper.input_helper', Mautic\CoreBundle\Helper\InputHelper::class);
    $services->set('mautic.helper.file_uploader', Mautic\CoreBundle\Helper\FileUploader::class);
    $services->set('mautic.helper.file_path_resolver', Mautic\CoreBundle\Helper\FilePathResolver::class);
    $services->set('mautic.helper.file_properties', Mautic\CoreBundle\Helper\FileProperties::class);
    $services->set('mautic.helper.trailing_slash', Mautic\CoreBundle\Helper\TrailingSlashHelper::class);
    $services->set('mautic.helper.token_builder', Mautic\CoreBundle\Helper\BuilderTokenHelper::class);
    $services->set('mautic.helper.token_builder.factory', Mautic\CoreBundle\Helper\BuilderTokenHelperFactory::class);
    $services->set('symfony.filesystem', Symfony\Component\Filesystem\Filesystem::class);
    $services->set('mautic.filesystem', Mautic\CoreBundle\Helper\Filesystem::class);
    $services->set('symfony.finder', Symfony\Component\Finder\Finder::class);
    $services->set('mautic.core.errorhandler.subscriber', Mautic\CoreBundle\EventListener\ErrorHandlingListener::class)->tag('kernel.event_subscriber');
    $services->set('mautic.configurator', Mautic\CoreBundle\Configurator\Configurator::class);
    $services->set('mautic.di.env_processor.nullable', Mautic\CoreBundle\DependencyInjection\EnvProcessor\NullableProcessor::class)->tag('container.env_var_processor');
    $services->set('mautic.di.env_processor.int_nullable', Mautic\CoreBundle\DependencyInjection\EnvProcessor\IntNullableProcessor::class)->tag('container.env_var_processor');
    $services->set('mautic.di.env_processor.mauticconst', Mautic\CoreBundle\DependencyInjection\EnvProcessor\MauticConstProcessor::class)->tag('container.env_var_processor');
    $services->set('mautic.cipher.openssl', Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher::class);
    $services->set('mautic.route_loader', Mautic\CoreBundle\Loader\RouteLoader::class)->tag('routing.loader');
    $services->set('mautic.page.helper.factory', Mautic\CoreBundle\Factory\PageHelperFactory::class);

    $services->set(Mautic\CoreBundle\Doctrine\Provider\VersionProvider::class);
    $services->set(Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider::class);

    $services->set('mautic.generated.columns.doctrine.listener', Mautic\CoreBundle\EventListener\DoctrineGeneratedColumnsListener::class)->tag('doctrine.event_listener', ['event' => 'postGenerateSchema', 'lazy' => true]);
    $services->set('mautic.helper.update', Mautic\CoreBundle\Helper\UpdateHelper::class);
    $services->set('mautic.helper.update.release_parser', Mautic\CoreBundle\Helper\Update\Github\ReleaseParser::class);
    $services->set('mautic.helper.url', Mautic\CoreBundle\Helper\UrlHelper::class);
    $services->set('mautic.helper.composer', Mautic\CoreBundle\Helper\ComposerHelper::class);
    $services->set('mautic.helper.menu', Mautic\CoreBundle\Menu\MenuHelper::class);
    $services->set('mautic.helper.hash', Mautic\CoreBundle\Helper\HashHelper\HashHelper::class);
    $services->set('mautic.helper.random', Mautic\CoreBundle\Helper\RandomHelper\RandomHelper::class);
    $services->set('mautic.helper.command', Mautic\CoreBundle\Helper\CommandHelper::class);
    $services->set('mautic.menu.builder', Mautic\CoreBundle\Menu\MenuBuilder::class);

    $services->set('mautic.form.list.validator.circular', Mautic\CoreBundle\Form\Validator\Constraints\CircularDependencyValidator::class)->tag('validator.constraint_validator');
    $services->set('mautic.maxmind.doNotSellList', Mautic\CoreBundle\IpLookup\DoNotSellList\MaxMindDoNotSellList::class);

    $services->set('mautic.monolog.handler', Mautic\CoreBundle\Monolog\Handler\FileLogHandler::class)
        ->arg('$exceptionFormatter', \Symfony\Component\DependencyInjection\Loader\Configurator\service('mautic.monolog.fulltrace.formatter'));

    $services->set('mautic.update.step.delete_cache', Mautic\CoreBundle\Update\Step\DeleteCacheStep::class)->tag('mautic.update_step');
    $services->set('mautic.update.step.finalize', Mautic\CoreBundle\Update\Step\FinalizeUpdateStep::class)->tag('mautic.update_step');
    $services->set('mautic.update.step.install_new_files', Mautic\CoreBundle\Update\Step\InstallNewFilesStep::class)->tag('mautic.update_step');
    $services->set('mautic.update.step.remove_deleted_files', Mautic\CoreBundle\Update\Step\RemoveDeletedFilesStep::class)->tag('mautic.update_step');
    $services->set('mautic.update.step.update_schema', Mautic\CoreBundle\Update\Step\UpdateSchemaStep::class)->tag('mautic.update_step');
    $services->set('mautic.update.step.update_translations', Mautic\CoreBundle\Update\Step\UpdateTranslationsStep::class)->tag('mautic.update_step');
    $services->set('mautic.update.step.checks', Mautic\CoreBundle\Update\Step\PreUpdateChecksStep::class)->tag('mautic.update_step');
    $services->set('mautic.update.checks.php', Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckPhpVersion::class)->tag('mautic.update_check');
    $services->set('mautic.update.checks.database', Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckDatabaseDriverAndVersion::class)->tag('mautic.update_check');
    $services->set('mautic.core.validator.file_upload', Mautic\CoreBundle\Validator\FileUploadValidator::class);

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
