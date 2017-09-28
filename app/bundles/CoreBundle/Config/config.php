<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Symfony\Component\Filesystem\Filesystem;

return [
    'routes' => [
        'main' => [
            'mautic_core_ajax' => [
                'path'       => '/ajax',
                'controller' => 'MauticCoreBundle:Ajax:delegateAjax',
            ],
            'mautic_core_update' => [
                'path'       => '/update',
                'controller' => 'MauticCoreBundle:Update:index',
            ],
            'mautic_core_update_schema' => [
                'path'       => '/update/schema',
                'controller' => 'MauticCoreBundle:Update:schema',
            ],
            'mautic_core_form_action' => [
                'path'       => '/action/{objectAction}/{objectModel}/{objectId}',
                'controller' => 'MauticCoreBundle:Form:execute',
                'defaults'   => [
                    'objectModel' => '',
                ],
            ],
            'mautic_core_file_action' => [
                'path'       => '/file/{objectAction}/{objectId}',
                'controller' => 'MauticCoreBundle:File:execute',
            ],
            'mautic_themes_index' => [
                'path'       => '/themes',
                'controller' => 'MauticCoreBundle:Theme:index',
            ],
            'mautic_themes_action' => [
                'path'       => '/themes/{objectAction}/{objectId}',
                'controller' => 'MauticCoreBundle:Theme:execute',
            ],
        ],
        'public' => [
            'mautic_js' => [
                'path'       => '/mtc.js',
                'controller' => 'MauticCoreBundle:Js:index',
            ],
            'mautic_base_index' => [
                'path'       => '/',
                'controller' => 'MauticCoreBundle:Default:index',
            ],
            'mautic_secure_root' => [
                'path'       => '/s',
                'controller' => 'MauticCoreBundle:Default:redirectSecureRoot',
            ],
            'mautic_secure_root_slash' => [
                'path'       => '/s/',
                'controller' => 'MauticCoreBundle:Default:redirectSecureRoot',
            ],
            'mautic_remove_trailing_slash' => [
                'path'         => '/{url}',
                'controller'   => 'MauticCoreBundle:Common:removeTrailingSlash',
                'method'       => 'GET',
                'requirements' => [
                    'url' => '.*/$',
                ],
            ],
        ],
        'api' => [
            'mautic_core_api_file_list' => [
                'path'       => '/files/{dir}',
                'controller' => 'MauticCoreBundle:Api\FileApi:list',
            ],
            'mautic_core_api_file_create' => [
                'path'       => '/files/{dir}/new',
                'controller' => 'MauticCoreBundle:Api\FileApi:create',
                'method'     => 'POST',
            ],
            'mautic_core_api_file_delete' => [
                'path'       => '/files/{dir}/{file}/delete',
                'controller' => 'MauticCoreBundle:Api\FileApi:delete',
                'method'     => 'DELETE',
            ],
            'mautic_core_api_theme_list' => [
                'path'       => '/themes',
                'controller' => 'MauticCoreBundle:Api\ThemeApi:list',
            ],
            'mautic_core_api_theme_get' => [
                'path'       => '/themes/{theme}',
                'controller' => 'MauticCoreBundle:Api\ThemeApi:get',
            ],
            'mautic_core_api_theme_create' => [
                'path'       => '/themes/new',
                'controller' => 'MauticCoreBundle:Api\ThemeApi:new',
                'method'     => 'POST',
            ],
            'mautic_core_api_theme_delete' => [
                'path'       => '/themes/{theme}/delete',
                'controller' => 'MauticCoreBundle:Api\ThemeApi:delete',
                'method'     => 'DELETE',
            ],
            'mautic_core_api_stats' => [
               'path'       => '/stats/{table}',
               'controller' => 'MauticCoreBundle:Api\StatsApi:list',
               'defaults'   => [
                    'table' => '',
                ],
           ],
        ],
    ],
    'menu' => [
        'main' => [
            'mautic.core.components' => [
                'id'        => 'mautic_components_root',
                'iconClass' => 'fa-puzzle-piece',
                'priority'  => 60,
            ],
            'mautic.core.channels' => [
                'id'        => 'mautic_channels_root',
                'iconClass' => 'fa-rss',
                'priority'  => 40,
            ],
        ],
        'admin' => [
            'mautic.theme.menu.index' => [
                'route'     => 'mautic_themes_index',
                'iconClass' => 'fa-newspaper-o',
                'id'        => 'mautic_themes_index',
                'access'    => 'core:themes:view',
            ],
        ],
        'extra' => [
            'priority' => -1000,
            'items'    => [
                'name'     => 'extra',
                'children' => [],
            ],
        ],
        'profile' => [
            'priority' => -1000,
            'items'    => [
                'name'     => 'profile',
                'children' => [],
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.core.subscriber' => [
                'class'     => 'Mautic\CoreBundle\EventListener\CoreSubscriber',
                'arguments' => [
                    'mautic.helper.bundle',
                    'mautic.helper.menu',
                    'mautic.helper.user',
                    'templating.helper.assets',
                    'mautic.helper.core_parameters',
                    'security.context',
                    'mautic.user.model.user',
                ],
            ],
            'mautic.core.environment.subscriber' => [
                'class'     => 'Mautic\CoreBundle\EventListener\EnvironmentSubscriber',
                'arguments' => [
                    'mautic.helper.cookie',
                ],
            ],
            'mautic.core.configbundle.subscriber' => [
                'class'     => 'Mautic\CoreBundle\EventListener\ConfigSubscriber',
                'arguments' => [
                    'mautic.helper.language',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.webpush.js.subscriber' => [
                'class' => 'Mautic\CoreBundle\EventListener\BuildJsSubscriber',
            ],
            'mautic.core.dashboard.subscriber' => [
                'class'     => 'Mautic\CoreBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.core.maintenance.subscriber' => [
                'class'     => 'Mautic\CoreBundle\EventListener\MaintenanceSubscriber',
                'arguments' => [
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.core.stats.subscriber' => [
                'class'     => \Mautic\CoreBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.core.assets.subscriber' => [
                'class'     => 'Mautic\CoreBundle\EventListener\AssetsSubscriber',
                'arguments' => [
                    'templating.helper.assets',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.spacer' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SpacerType',
                'alias' => 'spacer',
            ],
            'mautic.form.type.tel' => [
                'class' => 'Mautic\CoreBundle\Form\Type\TelType',
                'alias' => 'tel',
            ],
            'mautic.form.type.button_group' => [
                'class' => 'Mautic\CoreBundle\Form\Type\ButtonGroupType',
                'alias' => 'button_group',
            ],
            'mautic.form.type.yesno_button_group' => [
                'class' => 'Mautic\CoreBundle\Form\Type\YesNoButtonGroupType',
                'alias' => 'yesno_button_group',
            ],
            'mautic.form.type.standalone_button' => [
                'class' => 'Mautic\CoreBundle\Form\Type\StandAloneButtonType',
                'alias' => 'standalone_button',
            ],
            'mautic.form.type.form_buttons' => [
                'class' => 'Mautic\CoreBundle\Form\Type\FormButtonsType',
                'alias' => 'form_buttons',
            ],
            'mautic.form.type.hidden_entity' => [
                'class'     => 'Mautic\CoreBundle\Form\Type\HiddenEntityType',
                'alias'     => 'hidden_entity',
                'arguments' => 'doctrine.orm.entity_manager',
            ],
            'mautic.form.type.sortablelist' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SortableListType',
                'alias' => 'sortablelist',
            ],
            'mautic.form.type.dynamiclist' => [
                'class' => 'Mautic\CoreBundle\Form\Type\DynamicListType',
                'alias' => 'dynamiclist',
            ],
            'mautic.form.type.coreconfig' => [
                'class'     => 'Mautic\CoreBundle\Form\Type\ConfigType',
                'arguments' => [
                    'translator',
                    'mautic.helper.language',
                    'mautic.ip_lookup.factory',
                    '%mautic.supported_languages%',
                    '%mautic.ip_lookup_services%',
                    'mautic.ip_lookup',
                ],
                'alias' => 'coreconfig',
            ],
            'mautic.form.type.coreconfig.iplookup_download_data_store_button' => [
                'class'     => 'Mautic\CoreBundle\Form\Type\IpLookupDownloadDataStoreButtonType',
                'alias'     => 'iplookup_download_data_store_button',
                'arguments' => [
                    'mautic.helper.template.date',
                    'translator',
                ],
            ],
            'mautic.form.type.theme_list' => [
                'class'     => 'Mautic\CoreBundle\Form\Type\ThemeListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'theme_list',
            ],
            'mautic.form.type.daterange' => [
                'class'     => 'Mautic\CoreBundle\Form\Type\DateRangeType',
                'arguments' => 'mautic.factory',
                'alias'     => 'daterange',
            ],
            'mautic.form.type.builder.section' => [
                'class'     => 'Mautic\CoreBundle\Form\Type\BuilderSectionType',
                'arguments' => 'mautic.factory',
                'alias'     => 'builder_section',
            ],
            'mautic.form.type.slot' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotType',
                'alias' => 'slot',
            ],
            'mautic.form.type.slot.button' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotButtonType',
                'alias' => 'slot_button',
            ],
            'mautic.form.type.slot.image' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotImageType',
                'alias' => 'slot_image',
            ],
            'mautic.form.type.slot.separator' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotSeparatorType',
                'alias' => 'slot_separator',
            ],
            'mautic.form.type.slot.imagecard' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotImageCardType',
                'alias' => 'slot_imagecard',
            ],
            'mautic.form.type.slot.imagecaption' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotImageCaptionType',
                'alias' => 'slot_imagecaption',
            ],
            'mautic.form.type.slot.socialshare' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotSocialShareType',
                'alias' => 'slot_socialshare',
            ],
            'mautic.form.type.slot.socialfollow' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotSocialFollowType',
                'alias' => 'slot_socialfollow',
            ],
            'mautic.form.type.slot.codemode' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotCodeModeType',
                'alias' => 'slot_codemode',
            ],
            'mautic.form.type.theme.upload' => [
                'class' => 'Mautic\CoreBundle\Form\Type\ThemeUploadType',
                'alias' => 'theme_upload',
            ],
            'mautic.form.type.slot.dynamiccontent' => [
                'class' => 'Mautic\CoreBundle\Form\Type\SlotDynamicContentType',
                'alias' => 'slot_dynamiccontent',
            ],
            'mautic.form.type.dynamic_content_filter' => [
                'class' => \Mautic\CoreBundle\Form\Type\DynamicContentFilterType::class,
                'alias' => 'dynamic_content_filter',
            ],
            'mautic.form.type.dynamic_content_filter_entry' => [
                'class'     => \Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryType::class,
                'alias'     => 'dynamic_content_filter_entry',
                'arguments' => [
                    'mautic.lead.model.list',
                    'mautic.stage.model.stage',
                ],
            ],
            'mautic.form.type.dynamic_content_filter_entry_filters' => [
                'class'     => \Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryFiltersType::class,
                'alias'     => 'dynamic_content_filter_entry_filters',
                'arguments' => [
                    'translator',
                ],
                'methodCalls' => [
                    'setConnection' => [
                        'database_connection',
                    ],
                ],
            ],
            'mautic.form.type.entity_lookup' => [
                'class'     => \Mautic\CoreBundle\Form\Type\EntityLookupType::class,
                'arguments' => [
                    'mautic.model.factory',
                    'translator',
                    'database_connection',
                    'router',
                ],
            ],
        ],
        'helpers' => [
            'mautic.helper.template.menu' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\MenuHelper',
                'arguments' => ['knp_menu.helper'],
                'alias'     => 'menu',
            ],
            'mautic.helper.template.date' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\DateHelper',
                'arguments' => [
                    '%mautic.date_format_full%',
                    '%mautic.date_format_short%',
                    '%mautic.date_format_dateonly%',
                    '%mautic.date_format_timeonly%',
                    'translator',
                ],
                'alias' => 'date',
            ],
            'mautic.helper.template.exception' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\ExceptionHelper',
                'arguments' => '%kernel.root_dir%',
                'alias'     => 'exception',
            ],
            'mautic.helper.template.gravatar' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\GravatarHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'gravatar',
            ],
            'mautic.helper.template.analytics' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\AnalyticsHelper',
                'alias'     => 'analytics',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.helper.cookie',
                    'mautic.lead.model.lead',
                ],
            ],
            'mautic.helper.template.mautibot' => [
                'class' => 'Mautic\CoreBundle\Templating\Helper\MautibotHelper',
                'alias' => 'mautibot',
            ],
            'mautic.helper.template.canvas' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\SidebarCanvasHelper',
                'arguments' => [
                    'event_dispatcher',
                ],
                'alias' => 'canvas',
            ],
            'mautic.helper.template.button' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\ButtonHelper',
                'arguments' => [
                    'templating',
                    'translator',
                    'event_dispatcher',
                ],
                'alias' => 'buttons',
            ],
            'mautic.helper.template.content' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\ContentHelper',
                'arguments' => [
                    'templating',
                    'event_dispatcher',
                ],
                'alias' => 'content',
            ],
            'mautic.helper.template.formatter' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\FormatterHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'formatter',
            ],
            'mautic.helper.template.security' => [
                'class'     => 'Mautic\CoreBundle\Templating\Helper\SecurityHelper',
                'arguments' => [
                    'mautic.security',
                    'request_stack',
                    'event_dispatcher',
                ],
                'alias' => 'security',
            ],
            'mautic.helper.paths' => [
                'class'     => 'Mautic\CoreBundle\Helper\PathsHelper',
                'arguments' => [
                    'mautic.helper.user',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.helper.ip_lookup' => [
                'class'     => 'Mautic\CoreBundle\Helper\IpLookupHelper',
                'arguments' => [
                    'request_stack',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.core_parameters',
                    'mautic.ip_lookup',
                ],
            ],
            'mautic.helper.user' => [
                'class'     => 'Mautic\CoreBundle\Helper\UserHelper',
                'arguments' => [
                    'security.token_storage',
                ],
            ],
            'mautic.helper.core_parameters' => [
                'class'     => 'Mautic\CoreBundle\Helper\CoreParametersHelper',
                'arguments' => [
                    'kernel',
                ],
                'serviceAlias' => 'mautic.config',
            ],
            'mautic.helper.bundle' => [
                'class'     => 'Mautic\CoreBundle\Helper\BundleHelper',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'kernel',
                ],
            ],
            'mautic.helper.phone_number' => [
                'class' => 'Mautic\CoreBundle\Helper\PhoneNumberHelper',
            ],
            'mautic.helper.input_helper' => [
                'class' => InputHelper::class,
            ],
            'mautic.helper.file_uploader' => [
                'class'     => FileUploader::class,
                'arguments' => [
                    'mautic.helper.file_path_resolver',
                ],
            ],
            'mautic.helper.file_path_resolver' => [
                'class'     => FilePathResolver::class,
                'arguments' => [
                    'symfony.filesystem',
                    'mautic.helper.input_helper',
                ],
            ],
        ],
        'menus' => [
            'mautic.menu.main' => [
                'alias' => 'main',
            ],
            'mautic.menu.admin' => [
                'alias'   => 'admin',
                'options' => [
                    'template' => 'MauticCoreBundle:Menu:admin.html.php',
                ],
            ],
            'mautic.menu.extra' => [
                'alias'   => 'extra',
                'options' => [
                    'template' => 'MauticCoreBundle:Menu:extra.html.php',
                ],
            ],
            'mautic.menu.profile' => [
                'alias'   => 'profile',
                'options' => [
                    'template' => 'MauticCoreBundle:Menu:profile_inline.html.php',
                ],
            ],
        ],
        'other' => [
            'symfony.filesystem' => [
                'class' => Filesystem::class,
            ],

            // Error handler
            'mautic.core.errorhandler.subscriber' => [
                'class'     => 'Mautic\CoreBundle\EventListener\ErrorHandlingListener',
                'arguments' => [
                    'monolog.logger.mautic',
                    'monolog.logger',
                    "@=container.has('monolog.logger.chrome') ? container.get('monolog.logger.chrome') : null",
                ],
                'tag' => 'kernel.event_subscriber',
            ],

            // Configurator (used in installer and managing global config]
            'mautic.configurator' => [
                'class'     => 'Mautic\CoreBundle\Configurator\Configurator',
                'arguments' => [
                    'mautic.helper.paths',
                ],
            ],

            // Template helper overrides
            'templating.helper.assets.class'    => 'Mautic\CoreBundle\Templating\Helper\AssetsHelper',
            'templating.helper.slots.class'     => 'Mautic\CoreBundle\Templating\Helper\SlotsHelper',
            'templating.name_parser.class'      => 'Mautic\CoreBundle\Templating\TemplateNameParser',
            'templating.helper.form.class'      => 'Mautic\CoreBundle\Templating\Helper\FormHelper',
            'templating.engine.php.class'       => 'Mautic\CoreBundle\Templating\Engine\PhpEngine',
            'debug.templating.engine.php.class' => 'Mautic\CoreBundle\Templating\Engine\PhpEngine',
            // Translator overrides
            'translator.class'                   => 'Mautic\CoreBundle\Translation\Translator',
            'templating.helper.translator.class' => 'Mautic\CoreBundle\Templating\Helper\TranslatorHelper',
            // System uses
            'mautic.factory' => [
                'class'     => 'Mautic\CoreBundle\Factory\MauticFactory',
                'arguments' => 'service_container',
            ],
            'mautic.model.factory' => [
                'class'     => 'Mautic\CoreBundle\Factory\ModelFactory',
                'arguments' => 'service_container',
            ],
            'mautic.templating.name_parser' => [
                'class'     => 'Mautic\CoreBundle\Templating\TemplateNameParser',
                'arguments' => 'kernel',
            ],
            'mautic.route_loader' => [
                'class'     => 'Mautic\CoreBundle\Loader\RouteLoader',
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.core_parameters',
                ],
                'tag' => 'routing.loader',
            ],
            'mautic.security' => [
                'class'     => 'Mautic\CoreBundle\Security\Permissions\CorePermissions',
                'arguments' => [
                    'mautic.helper.user',
                    'translator',
                    '%mautic.parameters%',
                    '%mautic.bundles%',
                    '%mautic.plugin.bundles%',
                ],
            ],
            'mautic.translation.loader' => [
                'class'     => 'Mautic\CoreBundle\Loader\TranslationLoader',
                'arguments' => 'mautic.factory',
                'tag'       => 'translation.loader',
                'alias'     => 'mautic',
            ],
            'mautic.tblprefix_subscriber' => [
                'class'     => 'Mautic\CoreBundle\EventListener\DoctrineEventsSubscriber',
                'tag'       => 'doctrine.event_subscriber',
                'arguments' => '%mautic.db_table_prefix%',
            ],
            'mautic.exception.listener' => [
                'class'     => 'Mautic\CoreBundle\EventListener\ExceptionListener',
                'arguments' => [
                    'router',
                    '"MauticCoreBundle:Exception:show"',
                    'monolog.logger.mautic',
                ],
                'tag'          => 'kernel.event_listener',
                'tagArguments' => [
                    'event'    => 'kernel.exception',
                    'method'   => 'onKernelException',
                    'priority' => 255,
                ],
            ],
            'transifex' => [
                'class'     => 'BabDev\Transifex\Transifex',
                'arguments' => [
                    [
                        'api.username' => '%mautic.transifex_username%',
                        'api.password' => '%mautic.transifex_password%',
                    ],
                ],
            ],
            // Helpers
            'mautic.helper.assetgeneration' => [
                'class'     => 'Mautic\CoreBundle\Helper\AssetGenerationHelper',
                'arguments' => 'mautic.factory',
            ],
            'mautic.helper.cookie' => [
                'class'     => 'Mautic\CoreBundle\Helper\CookieHelper',
                'arguments' => [
                    '%mautic.cookie_path%',
                    '%mautic.cookie_domain%',
                    '%mautic.cookie_secure%',
                    '%mautic.cookie_httponly%',
                    'request_stack',
                ],
            ],
            'mautic.helper.cache_storage' => [
                'class'     => 'Mautic\CoreBundle\Helper\CacheStorageHelper',
                'arguments' => [
                    '"db"',
                    '%mautic.db_table_prefix%',
                    'doctrine.dbal.default_connection',
                    '%kernel.cache_dir%',
                ],
            ],
            'mautic.helper.update' => [
                'class'     => 'Mautic\CoreBundle\Helper\UpdateHelper',
                'arguments' => 'mautic.factory',
            ],
            'mautic.helper.cache' => [
                'class'     => 'Mautic\CoreBundle\Helper\CacheHelper',
                'arguments' => [
                    'kernel',
                ],
            ],
            'mautic.helper.templating' => [
                'class'     => 'Mautic\CoreBundle\Helper\TemplatingHelper',
                'arguments' => [
                    'kernel',
                ],
            ],
            'mautic.helper.theme' => [
                'class'     => 'Mautic\CoreBundle\Helper\ThemeHelper',
                'arguments' => [
                    'mautic.helper.paths',
                    'mautic.helper.templating',
                    'translator',
                ],
                'methodCalls' => [
                    'setDefaultTheme' => [
                        '%mautic.theme%',
                    ],
                ],
            ],
            'mautic.helper.encryption' => [
                'class'     => 'Mautic\CoreBundle\Helper\EncryptionHelper',
                'arguments' => 'mautic.factory',
            ],
            'mautic.helper.language' => [
                'class'     => 'Mautic\CoreBundle\Helper\LanguageHelper',
                'arguments' => 'mautic.factory',
            ],
            'mautic.helper.url' => [
                'class'     => 'Mautic\CoreBundle\Helper\UrlHelper',
                'arguments' => [
                    'mautic.http.connector',
                    '%mautic.link_shortener_url%',
                    'monolog.logger.mautic',
                ],
            ],
            // Menu
            'mautic.helper.menu' => [
                'class'     => 'Mautic\CoreBundle\Menu\MenuHelper',
                'arguments' => [
                    'mautic.security',
                    'request_stack',
                    '%mautic.parameters%',
                    'mautic.helper.integration',
                ],
            ],
            'mautic.menu_renderer' => [
                'class'     => 'Mautic\CoreBundle\Menu\MenuRenderer',
                'arguments' => [
                    'knp_menu.matcher',
                    'mautic.factory',
                    '%kernel.charset%',
                ],
                'tag'   => 'knp_menu.renderer',
                'alias' => 'mautic',
            ],
            'mautic.menu.builder' => [
                'class'     => 'Mautic\CoreBundle\Menu\MenuBuilder',
                'arguments' => [
                    'knp_menu.factory',
                    'knp_menu.matcher',
                    'event_dispatcher',
                    'mautic.helper.menu',
                ],
            ],
            // IP Lookup
            'mautic.ip_lookup.factory' => [
                'class'     => 'Mautic\CoreBundle\Factory\IpLookupFactory',
                'arguments' => [
                    '%mautic.ip_lookup_services%',
                    'monolog.logger.mautic',
                    'mautic.http.connector',
                    '%kernel.cache_dir%',
                ],
            ],
            'mautic.ip_lookup' => [
                'class'     => 'Mautic\CoreBundle\IpLookup\AbstractLookup', // bogus just to make cache compilation happy
                'factory'   => ['@mautic.ip_lookup.factory', 'getService'],
                'arguments' => [
                    '%mautic.ip_lookup_service%',
                    '%mautic.ip_lookup_auth%',
                    '%mautic.ip_lookup_config%',
                    'mautic.http.connector',
                ],
            ],
            // Other
            'mautic.http.connector' => [
                'class'   => 'Joomla\Http\Http',
                'factory' => ['Joomla\Http\HttpFactory', 'getHttp'],
            ],

            'twig.controller.exception.class' => 'Mautic\CoreBundle\Controller\ExceptionController',
            'monolog.handler.stream.class'    => 'Mautic\CoreBundle\Monolog\Handler\PhpHandler',

            // Form extensions
            'mautic.form.extension.custom' => [
                'class'     => 'Mautic\CoreBundle\Form\Extension\CustomFormExtension',
                'arguments' => [
                    'event_dispatcher',
                ],
                'tag'          => 'form.type_extension',
                'tagArguments' => [
                    'alias' => 'form',
                ],
            ],

            // Twig
            'templating.twig.extension.slot' => [
                'class'     => 'Mautic\CoreBundle\Templating\Twig\Extension\SlotExtension',
                'arguments' => [
                    'mautic.factory',
                ],
                'tag' => 'twig.extension',
            ],
            'templating.twig.extension.asset' => [
                'class'     => 'Mautic\CoreBundle\Templating\Twig\Extension\AssetExtension',
                'arguments' => [
                    'templating.helper.assets',
                ],
                'tag' => 'twig.extension',
            ],
            // Schema
            'mautic.schema.helper.factory' => [
                'class'     => 'Mautic\CoreBundle\Doctrine\Helper\SchemaHelperFactory',
                'arguments' => [
                    'mautic.schema.helper.table',
                    'mautic.schema.helper.index',
                    'mautic.schema.helper.column',
                ],
            ],
            'mautic.schema.helper.column' => [
                'class'     => 'Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper',
                'arguments' => [
                    'database_connection',
                    '%mautic.db_table_prefix%',
                ],
            ],
            'mautic.schema.helper.index' => [
                'class'     => 'Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper',
                'arguments' => [
                    'database_connection',
                    '%mautic.db_table_prefix%',
                ],
            ],
            'mautic.schema.helper.table' => [
                'class'     => 'Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper',
                'arguments' => [
                    'database_connection',
                    '%mautic.db_table_prefix%',
                    'mautic.schema.helper.column',
                ],
            ],
        ],
        'models' => [
            'mautic.core.model.auditlog' => [
                'class' => 'Mautic\CoreBundle\Model\AuditLogModel',
            ],
            'mautic.core.model.notification' => [
                'class'     => 'Mautic\CoreBundle\Model\NotificationModel',
                'arguments' => [
                    'mautic.helper.paths',
                    'mautic.helper.update',
                    'debril.reader',
                    'mautic.helper.core_parameters',
                ],
                'methodCalls' => [
                    'setDisableUpdates' => [
                        '%mautic.security.disableUpdates%',
                    ],
                ],
            ],
            'mautic.core.model.form' => [
                'class' => 'Mautic\CoreBundle\Model\FormModel',
            ],
            /* @deprecated - 2.4 to be removed in 3.0; use mautic.channel.model.queue instead */
            'mautic.core.model.messagequeue' => [
                'class'     => 'Mautic\CoreBundle\Model\MessageQueueModel',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'validator' => [
            'mautic.core.validator.file_upload' => [
                'class'     => FileUploadValidator::class,
                'arguments' => [
                    'translator',
                ],
            ],
        ],
    ],

    'ip_lookup_services' => [
        'freegeoip' => [
            'display_name' => 'Freegeoip.net',
            'class'        => 'Mautic\CoreBundle\IpLookup\FreegeoipLookup',
        ],
        'geobytes' => [
            'display_name' => 'Geobytes',
            'class'        => 'Mautic\CoreBundle\IpLookup\GeobytesLookup',
        ],
        'geoips' => [
            'display_name' => 'GeoIPs',
            'class'        => 'Mautic\CoreBundle\IpLookup\GeoipsLookup',
        ],
        'ipinfodb' => [
            'display_name' => 'IPInfoDB',
            'class'        => 'Mautic\CoreBundle\IpLookup\IpinfodbLookup',
        ],
        'maxmind_country' => [
            'display_name' => 'MaxMind - Country Geolocation',
            'class'        => 'Mautic\CoreBundle\IpLookup\MaxmindCountryLookup',
        ],
        'maxmind_omni' => [
            'display_name' => 'MaxMind - Insights (formerly Omni]',
            'class'        => 'Mautic\CoreBundle\IpLookup\MaxmindOmniLookup',
        ],
        'maxmind_precision' => [
            'display_name' => 'MaxMind - GeoIP2 Precision',
            'class'        => 'Mautic\CoreBundle\IpLookup\MaxmindPrecisionLookup',
        ],
        'maxmind_download' => [
            'display_name' => 'MaxMind - GeoLite2 City Download',
            'class'        => 'Mautic\CoreBundle\IpLookup\MaxmindDownloadLookup',
        ],
        'telize' => [
            'display_name' => 'Telize',
            'class'        => 'Mautic\CoreBundle\IpLookup\TelizeLookup',
        ],
        'ip2loctionlocal' => [
            'display_name' => 'IP2Location Local Bin File',
            'class'        => 'Mautic\CoreBundle\IpLookup\IP2LocationBinLookup',
        ],
        'ip2loctionapi' => [
            'display_name' => 'IP2Location Web Service',
            'class'        => 'Mautic\CoreBundle\IpLookup\IP2LocationAPILookup',
        ],
    ],

    'parameters' => [
        'site_url'                  => '',
        'webroot'                   => '',
        'cache_path'                => '%kernel.root_dir%/cache',
        'log_path'                  => '%kernel.root_dir%/logs',
        'image_path'                => 'media/images',
        'tmp_path'                  => '%kernel.root_dir%/cache',
        'theme'                     => 'Mauve',
        'db_driver'                 => 'pdo_mysql',
        'db_host'                   => '127.0.0.1',
        'db_port'                   => 3306,
        'db_name'                   => '',
        'db_user'                   => '',
        'db_password'               => '',
        'db_table_prefix'           => '',
        'db_server_version'         => '5.5',
        'locale'                    => 'en_US',
        'secret_key'                => '',
        'dev_hosts'                 => null,
        'trusted_hosts'             => null,
        'trusted_proxies'           => null,
        'rememberme_key'            => hash('sha1', uniqid(mt_rand())),
        'rememberme_lifetime'       => 31536000, //365 days in seconds
        'rememberme_path'           => '/',
        'rememberme_domain'         => '',
        'default_pagelimit'         => 30,
        'default_timezone'          => 'UTC',
        'date_format_full'          => 'F j, Y g:i a T',
        'date_format_short'         => 'D, M d',
        'date_format_dateonly'      => 'F j, Y',
        'date_format_timeonly'      => 'g:i a',
        'ip_lookup_service'         => 'maxmind_download',
        'ip_lookup_auth'            => '',
        'ip_lookup_config'          => [],
        'transifex_username'        => '',
        'transifex_password'        => '',
        'update_stability'          => 'stable',
        'cookie_path'               => '/',
        'cookie_domain'             => '',
        'cookie_secure'             => null,
        'cookie_httponly'           => false,
        'do_not_track_ips'          => [],
        'do_not_track_bots'         => ['MSNBOT', 'msnbot-media', 'bingbot', 'Googlebot', 'Google Web Preview', 'Mediapartners-Google', 'Baiduspider', 'Ezooms', 'YahooSeeker', 'Slurp', 'AltaVista', 'AVSearch', 'Mercator', 'Scooter', 'InfoSeek', 'Ultraseek', 'Lycos', 'Wget', 'YandexBot', 'Java/1.4.1_04', 'SiteBot', 'Exabot', 'AhrefsBot', 'MJ12bot', 'NetSeer crawler', 'TurnitinBot', 'magpie-crawler', 'Nutch Crawler', 'CMS Crawler', 'rogerbot', 'Domnutch', 'ssearch_bot', 'XoviBot', 'digincore', 'fr-crawler', 'SeznamBot', 'Seznam screenshot-generator', 'Facebot', 'facebookexternalhit'],
        'do_not_track_internal_ips' => [],
        'link_shortener_url'        => null,
        'cached_data_timeout'       => 10,
        'batch_sleep_time'          => 1,
        'batch_campaign_sleep_time' => false,
        'cors_restrict_domains'     => true,
        'cors_valid_domains'        => [],
        'rss_notification_url'      => 'https://mautic.com/?feed=rss2&tag=notification',
        'max_entity_lock_time'      => 0,
    ],
];
