<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Knp\Menu\MenuItem;
use Mautic\CoreBundle\Cache\MiddlewareCacheWarmer;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Controller\ExceptionController;
use Mautic\CoreBundle\DependencyInjection\EnvProcessor\IntNullableProcessor;
use Mautic\CoreBundle\DependencyInjection\EnvProcessor\MauticConstProcessor;
use Mautic\CoreBundle\DependencyInjection\EnvProcessor\NullableProcessor;
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper;
use Mautic\CoreBundle\Doctrine\Loader\MauticFixturesLoader;
use Mautic\CoreBundle\Doctrine\MigrationFactoryDecorator;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider;
use Mautic\CoreBundle\Doctrine\Provider\VersionProvider;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Mautic\CoreBundle\EventListener\ErrorHandlingListener;
use Mautic\CoreBundle\EventListener\ExceptionListener;
use Mautic\CoreBundle\EventListener\RouterSubscriber;
use Mautic\CoreBundle\Factory\IpLookupFactory;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\MaxMindDoNotSellDownloadHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckDatabaseDriverAndVersion;
use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckPhpVersion;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Mautic\CoreBundle\Loader\RouteLoader;
use Mautic\CoreBundle\Loader\TranslationLoader;
use Mautic\CoreBundle\Menu\MenuBuilder;
use Mautic\CoreBundle\Menu\MenuRenderer;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Monolog\Handler\FileLogHandler;
use Mautic\CoreBundle\Monolog\LogProcessor;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Security\Permissions\SystemPermissions;
use Mautic\CoreBundle\Service\LocalFileAdapterService;
use Mautic\CoreBundle\Twig\Extension\FormExtension;
use Mautic\CoreBundle\Twig\Extension\OverrideIncludeExtension;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Mautic\CoreBundle\Twig\Helper\ConfigHelper;
use Mautic\CoreBundle\Twig\Helper\ContentHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Mautic\CoreBundle\Twig\Helper\GravatarHelper;
use Mautic\CoreBundle\Twig\Helper\MautibotHelper;
use Mautic\CoreBundle\Twig\Helper\MenuHelper;
use Mautic\CoreBundle\Twig\Helper\SecurityHelper;
use Mautic\CoreBundle\Twig\Helper\VersionHelper;
use Mautic\CoreBundle\Update\Step\RemoveDeletedFilesStep;
use Mautic\CoreBundle\Update\Step\UpdateTranslationsStep;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Extra\String\StringExtension;

return function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set('twig.controller.exception.class', ExceptionController::class);

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
        'Twig/Helper/ThemeHelper.php',
        'Twig/Helper/MenuHelper.php',
        'Translation/MauticResourceTranslator.php',
        'Helper/Dsn/Dsn.php',
        'Cache/ResultCacheOptions.php',
    ];

    $services->set(MenuHelper::class)
        ->arg('$helper', service('knp_menu.helper'))
        ->tag('twig.helper', ['alias' => 'menu']);

    $services->load('Mautic\\CoreBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CoreBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set(AbstractLookup::class)
        ->factory([service(IpLookupFactory::class), 'getService'])
        ->args([param('mautic.ip_lookup_service'), param('mautic.ip_lookup_auth'), param('mautic.ip_lookup_config'), service('mautic.http.client')]);
    $services->set(HttpClientInterface::class)
        ->factory(HttpClient::create(...));
    $services->set(TranslationLoader::class)->tag('translation.loader', ['alias' => 'mautic']);
    $services->set(ThemeHelper::class)
        ->call('setDefaultTheme', [param('mautic.theme')]);
    $services->set(MenuRenderer::class)->tag('knp_menu.renderer', ['alias' => 'mautic']);

    $services->set(DateHelper::class)
        ->arg('$dateFullFormat', param('mautic.date_format_full'))
        ->arg('$dateShortFormat', param('mautic.date_format_short'))
        ->arg('$dateOnlyFormat', param('mautic.date_format_dateonly'))
        ->arg('$timeOnlyFormat', param('mautic.date_format_timeonly'))
        ->tag('twig.helper', ['alias' => 'date']);
    $services->set(GravatarHelper::class)->tag('twig.helper', ['alias' => 'gravatar']);
    $services->set(AnalyticsHelper::class)->tag('twig.helper', ['alias' => 'analytics']);
    $services->set(ConfigHelper::class)->tag('twig.helper', ['alias' => 'config']);
    $services->set(MautibotHelper::class)->tag('twig.helper', ['alias' => 'mautibot']);
    $services->set(ButtonHelper::class)->tag('twig.helper', ['alias' => 'buttons']);
    $services->set(ContentHelper::class)->tag('twig.helper', ['alias' => 'content']);
    $services->set(FormatterHelper::class)->tag('twig.helper', ['alias' => 'formatter']);
    $services->set(VersionHelper::class)->tag('twig.helper', ['alias' => 'version']);
    $services->set(SecurityHelper::class)->tag('twig.helper', ['alias' => 'security']);

    $services->set(LocalFileAdapterService::class)
        ->arg('$root', param('env(resolve:MAUTIC_EL_FINDER_PATH)'));

    $services->set(MaxMindDoNotSellDownloadHelper::class)
        ->arg('$auth', param('mautic.ip_lookup_auth'));
    $services->set(MiddlewareCacheWarmer::class)
        ->arg('$env', param('kernel.environment'))
        ->tag('kernel.cache_warmer');
    $services->set(CacheHelper::class)
        ->arg('$cacheDir', param('kernel.cache_dir'));
    $services->set(IpLookupFactory::class)
        ->arg('$lookupServices', param('mautic.ip_lookup_services'))
        ->arg('$cacheDir', param('kernel.cache_dir'));
    $services->set('mautic.schema.helper.column', ColumnSchemaHelper::class)
        ->arg('$prefix', param('mautic.db_table_prefix'));
    $services->alias(ColumnSchemaHelper::class, 'mautic.schema.helper.column');
    $services->set('mautic.schema.helper.index', IndexSchemaHelper::class)
        ->arg('$prefix', param('mautic.db_table_prefix'));
    $services->alias(IndexSchemaHelper::class, 'mautic.schema.helper.index');
    $services->set(TableSchemaHelper::class)
        ->arg('$prefix', param('mautic.db_table_prefix'));

    $services->set(RouterSubscriber::class)
        ->arg('$scheme', param('router.request_context.scheme'))
        ->arg('$host', param('router.request_context.host'))
        ->arg('$httpsPort', param('request_listener.https_port'))
        ->arg('$httpPort', param('request_listener.http_port'))
        ->arg('$baseUrl', param('router.request_context.base_url'));
    $services->set(PathsHelper::class)
        ->arg('$cacheDir', param('kernel.cache_dir'))
        ->arg('$logsDir', param('kernel.logs_dir'))
        ->arg('$rootDir', param('mautic.application_dir'));
    $services->set(BundleHelper::class)
        ->arg('$coreBundles', param('mautic.bundles'))
        ->arg('$pluginBundles', param('mautic.plugin.bundles'));

    $services->set('mautic.configurator', Configurator::class);
    $services->alias(Configurator::class, 'mautic.configurator');
    $services->set(OpenSSLCipher::class);

    $services->set(CorePermissions::class)
        ->arg('$bundles', param('mautic.bundles'))
        ->arg('$pluginBundles', param('mautic.plugin.bundles'));

    $services->set(ExceptionListener::class)
        ->arg('$controller', 'Mautic\CoreBundle\Controller\ExceptionController::showAction');

    $services->set(CookieHelper::class)
        ->arg('$path', param('mautic.cookie_path'))
        ->arg('$domain', param('mautic.cookie_domain'))
        ->arg('$secure', param('mautic.cookie_secure'))
        ->arg('$httponly', param('mautic.cookie_httponly'));

    $services->set(EncryptionHelper::class)
        ->arg(1, service(OpenSSLCipher::class));

    $services->set(Filesystem::class);

    $services->set('symfony.finder', Finder::class);
    $services->alias(Finder::class, 'symfony.finder');

    $services->set(RouteLoader::class)
        ->tag('routing.loader');

    $services->set(VersionProvider::class);
    $services->set(GeneratedColumnsProvider::class);

    $services->set(MauticFixturesLoader::class)
        ->arg('$fixturesLoader', service('doctrine.fixtures.loader'));

    $services->get(ErrorHandlingListener::class)
        ->arg('$logger', service('monolog.logger.mautic'))
        ->arg('$mainLogger', service('monolog.logger'))
        ->arg('$debugLogger', expr("container.has('monolog.logger.chrome') ? container.get('monolog.logger.chrome') : null"));

    $services->get(UpdateHelper::class)
        ->arg('$logger', service('monolog.logger.mautic'));

    $services->get(ComposerHelper::class)
        ->arg('$logger', service('monolog.logger.mautic'));

    $services->get(RemoveDeletedFilesStep::class)
        ->arg('$logger', service('monolog.logger.mautic'));

    $services->get(UpdateTranslationsStep::class)
        ->arg('$logger', service('monolog.logger.mautic'));

    $services->set(CheckPhpVersion::class);

    $services->set(CheckDatabaseDriverAndVersion::class);

    $services->get(LogProcessor::class)->tag('monolog.processor');

    $services->get(FileLogHandler::class)
        ->arg('$exceptionFormatter', service('mautic.monolog.fulltrace.formatter'));

    $services->set(NullableProcessor::class)
        ->tag('container.env_var_processor');

    $services->set(IntNullableProcessor::class)
        ->tag('container.env_var_processor');

    $services->set(MauticConstProcessor::class)
        ->tag('container.env_var_processor');

    // Explicitly register our Twig extension with high priority
    $services->set(OverrideIncludeExtension::class)
        ->tag('twig.extension', ['priority' => 100]);

    $services->get(FormExtension::class)
        ->arg('$formRenderer', service('twig.form.renderer'));

    $services->set('mautic.http.client', Client::class);
    $services->set(MigrationFactoryDecorator::class);

    $services->set(StringExtension::class);

    $services->alias(Client::class, 'mautic.http.client');
    $services->alias(ClientInterface::class, 'mautic.http.client');
    $services->alias(ContainerInterface::class, 'service_container');
    $services->alias(ArgumentResolverInterface::class, 'argument_resolver');

    $services->alias(VersionProviderInterface::class, VersionProvider::class);

    $services->get(AssetsHelper::class)
        ->tag('twig.helper', ['alias' => 'assets'])
        ->call('setVersion', [param('mautic.secret_key'), MAUTIC_VERSION]);

    $services->get(NotificationModel::class)->call('setDisableUpdates', ['%mautic.security.disableUpdates%']);
    $services->set(SystemPermissions::class);

    // Menus: each menu is a KnpMenu item built from the MenuBuilder,
    // rendered by a dedicated MenuRenderer so it can use its own template.
    $menuTemplates = [
        'main'    => [],
        'admin'   => ['template' => '@MauticCore/Menu/admin.html.twig'],
        'extra'   => ['template' => '@MauticCore/Menu/extra.html.twig'],
        'profile' => ['template' => '@MauticCore/Menu/profile_inline.html.twig'],
    ];

    foreach ($menuTemplates as $alias => $options) {
        $services->set('mautic.menu.'.$alias, MenuItem::class)
            ->factory([service(MenuBuilder::class), $alias.'Menu'])
            ->tag('knp_menu.menu', ['alias' => $alias]);

        $services->set('mautic.menu_renderer.'.$alias, MenuRenderer::class)
            ->args([service('knp_menu.matcher'), service('twig'), $options])
            ->tag('knp_menu.renderer', ['alias' => $alias]);
    }
};
