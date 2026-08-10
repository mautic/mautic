<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Twig\Extra\String\StringExtension;

return function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set('twig.controller.exception.class', Mautic\CoreBundle\Controller\ExceptionController::class);

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
        'Twig/Helper/MenuHelper.php',
        'Translation/TranslatorLoader.php',
        'Helper/Dsn/Dsn.php',
        'Cache/ResultCacheOptions.php',
    ];

    $services->set(Mautic\CoreBundle\Twig\Helper\MenuHelper::class)
        ->arg('$helper', \Symfony\Component\DependencyInjection\Loader\Configurator\service('knp_menu.helper'))
        ->tag('twig.helper', ['alias' => 'menu']);

    $services->load('Mautic\\CoreBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CoreBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set('mautic.helper.core_parameters', Mautic\CoreBundle\Helper\CoreParametersHelper::class)->tag('twig.helper');

    $services->alias(Mautic\CoreBundle\Helper\CoreParametersHelper::class, 'mautic.helper.core_parameters');
    $services->alias('mautic.config', 'mautic.helper.core_parameters');

    $services->set('mautic.ip_lookup', Mautic\CoreBundle\IpLookup\AbstractLookup::class)
        ->factory([service('mautic.ip_lookup.factory'), 'getService'])
        ->args([param('mautic.ip_lookup_service'), param('mautic.ip_lookup_auth'), param('mautic.ip_lookup_config'), service('mautic.http.client')]);
    $services->alias(Mautic\CoreBundle\IpLookup\AbstractLookup::class, 'mautic.ip_lookup');
    $services->set('mautic.native.connector', Symfony\Contracts\HttpClient\HttpClientInterface::class)
        ->factory(Symfony\Component\HttpClient\HttpClient::create(...));
    $services->alias(Symfony\Contracts\HttpClient\HttpClientInterface::class, 'mautic.native.connector');
    $services->set('mautic.translation.loader', Mautic\CoreBundle\Loader\TranslationLoader::class)->tag('translation.loader', ['alias' => 'mautic']);
    $services->alias(Mautic\CoreBundle\Loader\TranslationLoader::class, 'mautic.translation.loader');
    $services->set('mautic.helper.theme', Mautic\CoreBundle\Helper\ThemeHelper::class)
        ->call('setDefaultTheme', [param('mautic.theme')]);
    $services->alias(Mautic\CoreBundle\Helper\ThemeHelper::class, 'mautic.helper.theme');
    $services->set('mautic.menu_renderer', Mautic\CoreBundle\Menu\MenuRenderer::class)->tag('knp_menu.renderer', ['alias' => 'mautic']);
    $services->alias(Mautic\CoreBundle\Menu\MenuRenderer::class, 'mautic.menu_renderer');

    $services->set('mautic.helper.menu', Mautic\CoreBundle\Menu\MenuHelper::class);
    $services->alias(Mautic\CoreBundle\Menu\MenuHelper::class, 'mautic.helper.menu');
    $services->set('mautic.menu.builder', Mautic\CoreBundle\Menu\MenuBuilder::class);
    $services->alias(Mautic\CoreBundle\Menu\MenuBuilder::class, 'mautic.menu.builder');

    $services->set('mautic.helper.twig.date', Mautic\CoreBundle\Twig\Helper\DateHelper::class)
        ->arg('$dateFullFormat', param('mautic.date_format_full'))
        ->arg('$dateShortFormat', param('mautic.date_format_short'))
        ->arg('$dateOnlyFormat', param('mautic.date_format_dateonly'))
        ->arg('$timeOnlyFormat', param('mautic.date_format_timeonly'))
        ->tag('twig.helper', ['alias' => 'date']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\DateHelper::class, 'mautic.helper.twig.date');
    $services->set('mautic.helper.twig.gravatar', Mautic\CoreBundle\Twig\Helper\GravatarHelper::class)->tag('twig.helper', ['alias' => 'gravatar']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\GravatarHelper::class, 'mautic.helper.twig.gravatar');
    $services->set('mautic.helper.twig.analytics', Mautic\CoreBundle\Twig\Helper\AnalyticsHelper::class)->tag('twig.helper', ['alias' => 'analytics']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\AnalyticsHelper::class, 'mautic.helper.twig.analytics');
    $services->set('mautic.helper.twig.config', Mautic\CoreBundle\Twig\Helper\ConfigHelper::class)->tag('twig.helper', ['alias' => 'config']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\ConfigHelper::class, 'mautic.helper.twig.config');
    $services->set('mautic.helper.twig.mautibot', Mautic\CoreBundle\Twig\Helper\MautibotHelper::class)->tag('twig.helper', ['alias' => 'mautibot']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\MautibotHelper::class, 'mautic.helper.twig.mautibot');
    $services->set('mautic.helper.twig.button', Mautic\CoreBundle\Twig\Helper\ButtonHelper::class)->tag('twig.helper', ['alias' => 'buttons']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\ButtonHelper::class, 'mautic.helper.twig.button');
    $services->set('mautic.helper.twig.content', Mautic\CoreBundle\Twig\Helper\ContentHelper::class)->tag('twig.helper', ['alias' => 'content']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\ContentHelper::class, 'mautic.helper.twig.content');
    $services->set('mautic.helper.twig.formatter', Mautic\CoreBundle\Twig\Helper\FormatterHelper::class)->tag('twig.helper', ['alias' => 'formatter']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\FormatterHelper::class, 'mautic.helper.twig.formatter');
    $services->set('mautic.helper.twig.version', Mautic\CoreBundle\Twig\Helper\VersionHelper::class)->tag('twig.helper', ['alias' => 'version']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\VersionHelper::class, 'mautic.helper.twig.version');
    $services->set('mautic.helper.twig.security', Mautic\CoreBundle\Twig\Helper\SecurityHelper::class)->tag('twig.helper', ['alias' => 'security']);
    $services->alias(Mautic\CoreBundle\Twig\Helper\SecurityHelper::class, 'mautic.helper.twig.security');

    $services->set('mautic.core.service.local_file_adapter', Mautic\CoreBundle\Service\LocalFileAdapterService::class)
        ->arg('$root', param('env(resolve:MAUTIC_EL_FINDER_PATH)'));

    $services->alias(Mautic\CoreBundle\Service\LocalFileAdapterService::class, 'mautic.core.service.local_file_adapter');
    $services->set('mautic.helper.maxmind_do_not_sell_download', Mautic\CoreBundle\Helper\MaxMindDoNotSellDownloadHelper::class)
        ->arg('$auth', param('mautic.ip_lookup_auth'));
    $services->alias(Mautic\CoreBundle\Helper\MaxMindDoNotSellDownloadHelper::class, 'mautic.helper.maxmind_do_not_sell_download');
    $services->set('mautic.cache.warmer.middleware', Mautic\CoreBundle\Cache\MiddlewareCacheWarmer::class)
        ->arg('$env', param('kernel.environment'))
        ->tag('kernel.cache_warmer');
    $services->alias(Mautic\CoreBundle\Cache\MiddlewareCacheWarmer::class, 'mautic.cache.warmer.middleware');
    $services->set('mautic.helper.cache_storage', Mautic\CoreBundle\Helper\CacheStorageHelper::class)
        ->arg('$adaptor', 'db')
        ->arg('$namespace', param('mautic.db_table_prefix'))
        ->arg('$connection', service('doctrine.dbal.default_connection'))
        ->arg('$cacheDir', param('kernel.cache_dir'));
    $services->alias(Mautic\CoreBundle\Helper\CacheStorageHelper::class, 'mautic.helper.cache_storage');
    $services->set('mautic.helper.cache', Mautic\CoreBundle\Helper\CacheHelper::class)
        ->arg('$cacheDir', param('kernel.cache_dir'));
    $services->alias(Mautic\CoreBundle\Helper\CacheHelper::class, 'mautic.helper.cache');
    $services->set('mautic.ip_lookup.factory', Mautic\CoreBundle\Factory\IpLookupFactory::class)
        ->arg('$lookupServices', param('mautic.ip_lookup_services'))
        ->arg('$cacheDir', param('kernel.cache_dir'));
    $services->alias(Mautic\CoreBundle\Factory\IpLookupFactory::class, 'mautic.ip_lookup.factory');
    $services->set('mautic.schema.helper.column', Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper::class)
        ->arg('$prefix', param('mautic.db_table_prefix'));
    $services->alias(Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper::class, 'mautic.schema.helper.column');
    $services->set('mautic.schema.helper.index', Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper::class)
        ->arg('$prefix', param('mautic.db_table_prefix'));
    $services->alias(Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper::class, 'mautic.schema.helper.index');
    $services->set('mautic.schema.helper.table', Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper::class)
        ->arg('$prefix', param('mautic.db_table_prefix'));
    $services->alias(Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper::class, 'mautic.schema.helper.table');
    $services->set('mautic.maxmind.doNotSellList', Mautic\CoreBundle\IpLookup\DoNotSellList\MaxMindDoNotSellList::class);
    $services->alias(Mautic\CoreBundle\IpLookup\DoNotSellList\MaxMindDoNotSellList::class, 'mautic.maxmind.doNotSellList');
    $services->set('mautic.form.type.dynamic_content_filter_entry_filters', Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryFiltersType::class)
        ->call('setConnection', [service('database_connection')]);
    $services->alias(Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryFiltersType::class, 'mautic.form.type.dynamic_content_filter_entry_filters');

    $services->set('mautic.core.subscriber.router', Mautic\CoreBundle\EventListener\RouterSubscriber::class)
        ->arg('$scheme', param('router.request_context.scheme'))
        ->arg('$host', param('router.request_context.host'))
        ->arg('$httpsPort', param('request_listener.https_port'))
        ->arg('$httpPort', param('request_listener.http_port'))
        ->arg('$baseUrl', param('router.request_context.base_url'));
    $services->alias(Mautic\CoreBundle\EventListener\RouterSubscriber::class, 'mautic.core.subscriber.router');
    $services->set('mautic.helper.paths', Mautic\CoreBundle\Helper\PathsHelper::class)
        ->arg('$cacheDir', param('kernel.cache_dir'))
        ->arg('$logsDir', param('kernel.logs_dir'))
        ->arg('$rootDir', param('mautic.application_dir'));
    $services->alias(Mautic\CoreBundle\Helper\PathsHelper::class, 'mautic.helper.paths');
    $services->set('mautic.helper.bundle', Mautic\CoreBundle\Helper\BundleHelper::class)
        ->arg('$coreBundles', param('mautic.bundles'))
        ->arg('$pluginBundles', param('mautic.plugin.bundles'));
    $services->alias(Mautic\CoreBundle\Helper\BundleHelper::class, 'mautic.helper.bundle');
    $services->set('mautic.configurator', Mautic\CoreBundle\Configurator\Configurator::class);
    $services->alias(Mautic\CoreBundle\Configurator\Configurator::class, 'mautic.configurator');
    $services->set('mautic.cipher.openssl', Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher::class);
    $services->alias(Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher::class, 'mautic.cipher.openssl');
    $services->set('mautic.security', Mautic\CoreBundle\Security\Permissions\CorePermissions::class)
        ->arg('$bundles', param('mautic.bundles'))
        ->arg('$pluginBundles', param('mautic.plugin.bundles'));
    $services->alias(Mautic\CoreBundle\Security\Permissions\CorePermissions::class, 'mautic.security');

    $services->set('mautic.exception.listener', Mautic\CoreBundle\EventListener\ExceptionListener::class)
        ->arg('$controller', 'Mautic\CoreBundle\Controller\ExceptionController::showAction');

    $services->alias(Mautic\CoreBundle\EventListener\ExceptionListener::class, 'mautic.exception.listener');
    $services->set('mautic.helper.cookie', Mautic\CoreBundle\Helper\CookieHelper::class)
        ->arg('$path', param('mautic.cookie_path'))
        ->arg('$domain', param('mautic.cookie_domain'))
        ->arg('$secure', param('mautic.cookie_secure'))
        ->arg('$httponly', param('mautic.cookie_httponly'))
        ->tag('kernel.event_subscriber');
    $services->alias(Mautic\CoreBundle\Helper\CookieHelper::class, 'mautic.helper.cookie');

    $services->set(Mautic\CoreBundle\Helper\EncryptionHelper::class)
        ->args([
            service('mautic.helper.core_parameters'),
            service('mautic.cipher.openssl'),
        ]);

    $services->set('mautic.form.list.validator.circular', Mautic\CoreBundle\Form\Validator\Constraints\CircularDependencyValidator::class)->tag('validator.constraint_validator');
    $services->alias(Mautic\CoreBundle\Form\Validator\Constraints\CircularDependencyValidator::class, 'mautic.form.list.validator.circular');

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
    $services->set(Mautic\CoreBundle\Loader\RouteLoader::class)
        ->tag('routing.loader');

    $services->set(Mautic\CoreBundle\Doctrine\Provider\VersionProvider::class);
    $services->set(Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider::class);

    $services->get(Mautic\CoreBundle\EventListener\DoctrineGeneratedColumnsListener::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'))
        ->tag('doctrine.event_listener', ['event' => 'postGenerateSchema', 'lazy' => true]);
    $services->alias('mautic.generated.columns.doctrine.listener', Mautic\CoreBundle\EventListener\DoctrineGeneratedColumnsListener::class);

    $services->set(Mautic\CoreBundle\Doctrine\Loader\MauticFixturesLoader::class)
        ->arg('$fixturesLoader', \Symfony\Component\DependencyInjection\Loader\Configurator\service('doctrine.fixtures.loader'));

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
    $services->alias('mautic.core.service.bulk_notification', Mautic\CoreBundle\Service\BulkNotification::class);

    $services->get(Mautic\CoreBundle\Monolog\LogProcessor::class)->tag('monolog.processor');
    $services->alias('mautic.core.service.log_processor', Mautic\CoreBundle\Monolog\LogProcessor::class);

    $services->get(Mautic\CoreBundle\Monolog\Handler\FileLogHandler::class)
        ->arg('$exceptionFormatter', \Symfony\Component\DependencyInjection\Loader\Configurator\service('mautic.monolog.fulltrace.formatter'));
    $services->alias('mautic.monolog.handler', Mautic\CoreBundle\Monolog\Handler\FileLogHandler::class);

    $services->set(Mautic\CoreBundle\DependencyInjection\EnvProcessor\NullableProcessor::class)
        ->tag('container.env_var_processor');
    $services->alias('mautic.di.env_processor.nullable', Mautic\CoreBundle\DependencyInjection\EnvProcessor\NullableProcessor::class);

    $services->set(Mautic\CoreBundle\DependencyInjection\EnvProcessor\IntNullableProcessor::class)
        ->tag('container.env_var_processor');

    $services->alias('mautic.di.env_processor.int_nullable', Mautic\CoreBundle\DependencyInjection\EnvProcessor\IntNullableProcessor::class);

    $services->set(Mautic\CoreBundle\DependencyInjection\EnvProcessor\MauticConstProcessor::class)
        ->tag('container.env_var_processor');

    $services->alias('mautic.di.env_processor.mauticconst', Mautic\CoreBundle\DependencyInjection\EnvProcessor\MauticConstProcessor::class);

    $services->alias('mautic.helper.user', Mautic\CoreBundle\Helper\UserHelper::class);
    $services->alias('mautic.helper.ip_lookup', Mautic\CoreBundle\Helper\IpLookupHelper::class);
    $services->alias('mautic.helper.token_builder', Mautic\CoreBundle\Helper\BuilderTokenHelper::class);
    $services->alias('mautic.helper.token_builder.factory', Mautic\CoreBundle\Helper\BuilderTokenHelperFactory::class);
    $services->alias('mautic.helper.app_version', Mautic\CoreBundle\Helper\AppVersion::class);
    $services->alias('mautic.helper.command', Mautic\CoreBundle\Helper\CommandHelper::class);
    $services->alias('mautic.page.helper.factory', Mautic\CoreBundle\Factory\PageHelperFactory::class);

    $services->alias('mautic.core.repository.ip_address', Mautic\CoreBundle\Entity\IpAddressRepository::class);

    // Explicitly register our Twig extension with high priority
    $services->set(Mautic\CoreBundle\Twig\Extension\OverrideIncludeExtension::class)
        ->autowire()
        ->tag('twig.extension', ['priority' => 100]);

    $services->get(Mautic\CoreBundle\Twig\Extension\FormExtension::class)
        ->arg('$formRenderer', \Symfony\Component\DependencyInjection\Loader\Configurator\service('twig.form.renderer'));

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
    $services->set(Mautic\CoreBundle\Security\Permissions\SystemPermissions::class);
};
