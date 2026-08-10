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

    $services->set(Mautic\CoreBundle\Helper\CoreParametersHelper::class)->tag('twig.helper');

    $services->alias('mautic.helper.core_parameters', Mautic\CoreBundle\Helper\CoreParametersHelper::class);

    $services->set(Mautic\CoreBundle\IpLookup\AbstractLookup::class)
        ->factory([service('mautic.ip_lookup.factory'), 'getService'])
        ->args([param('mautic.ip_lookup_service'), param('mautic.ip_lookup_auth'), param('mautic.ip_lookup_config'), service('mautic.http.client')]);
    $services->set(Symfony\Contracts\HttpClient\HttpClientInterface::class)
        ->factory(Symfony\Component\HttpClient\HttpClient::create(...));
    $services->set(Mautic\CoreBundle\Loader\TranslationLoader::class)->tag('translation.loader', ['alias' => 'mautic']);
    $services->set(Mautic\CoreBundle\Helper\ThemeHelper::class)
        ->call('setDefaultTheme', [param('mautic.theme')]);
    $services->set(Mautic\CoreBundle\Menu\MenuRenderer::class)->tag('knp_menu.renderer', ['alias' => 'mautic']);

    $services->set(Mautic\CoreBundle\Menu\MenuHelper::class);
    $services->set(Mautic\CoreBundle\Menu\MenuBuilder::class);
    $services->alias('mautic.menu.builder', Mautic\CoreBundle\Menu\MenuBuilder::class);

    $services->set(Mautic\CoreBundle\Twig\Helper\DateHelper::class)
        ->arg('$dateFullFormat', param('mautic.date_format_full'))
        ->arg('$dateShortFormat', param('mautic.date_format_short'))
        ->arg('$dateOnlyFormat', param('mautic.date_format_dateonly'))
        ->arg('$timeOnlyFormat', param('mautic.date_format_timeonly'))
        ->tag('twig.helper', ['alias' => 'date']);
    $services->set(Mautic\CoreBundle\Twig\Helper\GravatarHelper::class)->tag('twig.helper', ['alias' => 'gravatar']);
    $services->set(Mautic\CoreBundle\Twig\Helper\AnalyticsHelper::class)->tag('twig.helper', ['alias' => 'analytics']);
    $services->set(Mautic\CoreBundle\Twig\Helper\ConfigHelper::class)->tag('twig.helper', ['alias' => 'config']);
    $services->set(Mautic\CoreBundle\Twig\Helper\MautibotHelper::class)->tag('twig.helper', ['alias' => 'mautibot']);
    $services->set(Mautic\CoreBundle\Twig\Helper\ButtonHelper::class)->tag('twig.helper', ['alias' => 'buttons']);
    $services->set(Mautic\CoreBundle\Twig\Helper\ContentHelper::class)->tag('twig.helper', ['alias' => 'content']);
    $services->set(Mautic\CoreBundle\Twig\Helper\FormatterHelper::class)->tag('twig.helper', ['alias' => 'formatter']);
    $services->set(Mautic\CoreBundle\Twig\Helper\VersionHelper::class)->tag('twig.helper', ['alias' => 'version']);
    $services->set(Mautic\CoreBundle\Twig\Helper\SecurityHelper::class)->tag('twig.helper', ['alias' => 'security']);

    $services->set(Mautic\CoreBundle\Service\LocalFileAdapterService::class)
        ->arg('$root', param('env(resolve:MAUTIC_EL_FINDER_PATH)'));

    $services->alias('mautic.core.service.local_file_adapter', Mautic\CoreBundle\Service\LocalFileAdapterService::class);
    $services->set(Mautic\CoreBundle\Helper\MaxMindDoNotSellDownloadHelper::class)
        ->arg('$auth', param('mautic.ip_lookup_auth'));
    $services->set(Mautic\CoreBundle\Cache\MiddlewareCacheWarmer::class)
        ->arg('$env', param('kernel.environment'))
        ->tag('kernel.cache_warmer');
    $services->set(Mautic\CoreBundle\Helper\CacheHelper::class)
        ->arg('$cacheDir', param('kernel.cache_dir'));
    $services->set(Mautic\CoreBundle\Factory\IpLookupFactory::class)
        ->arg('$lookupServices', param('mautic.ip_lookup_services'))
        ->arg('$cacheDir', param('kernel.cache_dir'));
    $services->alias('mautic.ip_lookup.factory', Mautic\CoreBundle\Factory\IpLookupFactory::class);
    $services->set('mautic.schema.helper.column', Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper::class)
        ->arg('$prefix', param('mautic.db_table_prefix'));
    $services->alias(Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper::class, 'mautic.schema.helper.column');
    $services->set('mautic.schema.helper.index', Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper::class)
        ->arg('$prefix', param('mautic.db_table_prefix'));
    $services->alias(Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper::class, 'mautic.schema.helper.index');
    $services->set(Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper::class)
        ->arg('$prefix', param('mautic.db_table_prefix'));
    $services->set(Mautic\CoreBundle\IpLookup\DoNotSellList\MaxMindDoNotSellList::class);
    $services->set(Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryFiltersType::class)
        ->call('setConnection', [service('database_connection')]);

    $services->set(Mautic\CoreBundle\EventListener\RouterSubscriber::class)
        ->arg('$scheme', param('router.request_context.scheme'))
        ->arg('$host', param('router.request_context.host'))
        ->arg('$httpsPort', param('request_listener.https_port'))
        ->arg('$httpPort', param('request_listener.http_port'))
        ->arg('$baseUrl', param('router.request_context.base_url'));
    $services->set(Mautic\CoreBundle\Helper\PathsHelper::class)
        ->arg('$cacheDir', param('kernel.cache_dir'))
        ->arg('$logsDir', param('kernel.logs_dir'))
        ->arg('$rootDir', param('mautic.application_dir'));
    $services->alias('mautic.helper.paths', Mautic\CoreBundle\Helper\PathsHelper::class);
    $services->set(Mautic\CoreBundle\Helper\BundleHelper::class)
        ->arg('$coreBundles', param('mautic.bundles'))
        ->arg('$pluginBundles', param('mautic.plugin.bundles'));
    $services->alias('mautic.helper.bundle', Mautic\CoreBundle\Helper\BundleHelper::class);
    $services->set('mautic.configurator', Mautic\CoreBundle\Configurator\Configurator::class);
    $services->alias(Mautic\CoreBundle\Configurator\Configurator::class, 'mautic.configurator');
    $services->set(Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher::class);
    $services->alias('mautic.cipher.openssl', Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher::class);
    $services->set('mautic.security', Mautic\CoreBundle\Security\Permissions\CorePermissions::class)
        ->arg('$bundles', param('mautic.bundles'))
        ->arg('$pluginBundles', param('mautic.plugin.bundles'));
    $services->alias(Mautic\CoreBundle\Security\Permissions\CorePermissions::class, 'mautic.security');

    $services->set(Mautic\CoreBundle\EventListener\ExceptionListener::class)
        ->arg('$controller', 'Mautic\CoreBundle\Controller\ExceptionController::showAction');

    $services->set(Mautic\CoreBundle\Helper\CookieHelper::class)
        ->arg('$path', param('mautic.cookie_path'))
        ->arg('$domain', param('mautic.cookie_domain'))
        ->arg('$secure', param('mautic.cookie_secure'))
        ->arg('$httponly', param('mautic.cookie_httponly'))
        ->tag('kernel.event_subscriber');

    $services->set(Mautic\CoreBundle\Helper\EncryptionHelper::class)
        ->args([
            service('mautic.helper.core_parameters'),
            service('mautic.cipher.openssl'),
        ]);

    $services->set(Mautic\CoreBundle\Form\Validator\Constraints\CircularDependencyValidator::class)->tag('validator.constraint_validator');

    /* @deprecated to be removed in Mautic 4. Use 'mautic.filesystem' instead. */
    $services->set('symfony.filesystem', Symfony\Component\Filesystem\Filesystem::class);
    $services->alias(Symfony\Component\Filesystem\Filesystem::class, 'symfony.filesystem');

    $services->set('symfony.finder', Symfony\Component\Finder\Finder::class);
    $services->alias(Symfony\Component\Finder\Finder::class, 'symfony.finder');

    $services->set(Mautic\CoreBundle\Loader\RouteLoader::class)
        ->tag('routing.loader');

    $services->set(Mautic\CoreBundle\Doctrine\Provider\VersionProvider::class);
    $services->set(Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider::class);

    $services->get(Mautic\CoreBundle\EventListener\DoctrineGeneratedColumnsListener::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'))
        ->tag('doctrine.event_listener', ['event' => 'postGenerateSchema', 'lazy' => true]);

    $services->set(Mautic\CoreBundle\Doctrine\Loader\MauticFixturesLoader::class)
        ->arg('$fixturesLoader', \Symfony\Component\DependencyInjection\Loader\Configurator\service('doctrine.fixtures.loader'));

    $services->get(Mautic\CoreBundle\EventListener\ErrorHandlingListener::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'))
        ->arg('$mainLogger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger'))
        ->arg('$debugLogger', \Symfony\Component\DependencyInjection\Loader\Configurator\expr("container.has('monolog.logger.chrome') ? container.get('monolog.logger.chrome') : null"));

    $services->get(Mautic\CoreBundle\Helper\UpdateHelper::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'));

    $services->get(Mautic\CoreBundle\Helper\ComposerHelper::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'));

    $services->get(Mautic\CoreBundle\Update\Step\DeleteCacheStep::class)->tag('mautic.update_step');

    $services->get(Mautic\CoreBundle\Update\Step\FinalizeUpdateStep::class)->tag('mautic.update_step');

    $services->get(Mautic\CoreBundle\Update\Step\InstallNewFilesStep::class)->tag('mautic.update_step');

    $services->get(Mautic\CoreBundle\Update\Step\RemoveDeletedFilesStep::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'))
        ->tag('mautic.update_step');

    $services->get(Mautic\CoreBundle\Update\Step\UpdateSchemaStep::class)->tag('mautic.update_step');

    $services->get(Mautic\CoreBundle\Update\Step\UpdateTranslationsStep::class)
        ->arg('$logger', \Symfony\Component\DependencyInjection\Loader\Configurator\service('monolog.logger.mautic'))
        ->tag('mautic.update_step');

    $services->get(Mautic\CoreBundle\Update\Step\PreUpdateChecksStep::class)->tag('mautic.update_step');

    $services->set(Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckPhpVersion::class)->tag('mautic.update_check');

    $services->set(Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckDatabaseDriverAndVersion::class)->tag('mautic.update_check');

    $services->get(Mautic\CoreBundle\Monolog\LogProcessor::class)->tag('monolog.processor');

    $services->get(Mautic\CoreBundle\Monolog\Handler\FileLogHandler::class)
        ->arg('$exceptionFormatter', \Symfony\Component\DependencyInjection\Loader\Configurator\service('mautic.monolog.fulltrace.formatter'));
    $services->alias('mautic.monolog.handler', Mautic\CoreBundle\Monolog\Handler\FileLogHandler::class);

    $services->set(Mautic\CoreBundle\DependencyInjection\EnvProcessor\NullableProcessor::class)
        ->tag('container.env_var_processor');

    $services->set(Mautic\CoreBundle\DependencyInjection\EnvProcessor\IntNullableProcessor::class)
        ->tag('container.env_var_processor');

    $services->set(Mautic\CoreBundle\DependencyInjection\EnvProcessor\MauticConstProcessor::class)
        ->tag('container.env_var_processor');

    $services->alias('mautic.helper.token_builder', Mautic\CoreBundle\Helper\BuilderTokenHelper::class);

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
