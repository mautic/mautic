<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes'     => array(
        'main'   => array(
            'mautic_core_ajax'        => array(
                'path'       => '/ajax',
                'controller' => 'MauticCoreBundle:Ajax:delegateAjax'
            ),
            'mautic_core_update'      => array(
                'path'       => '/update',
                'controller' => 'MauticCoreBundle:Update:index'
            ),
            'mautic_core_update_schema'      => array(
                'path'       => '/update/schema',
                'controller' => 'MauticCoreBundle:Update:schema'
            ),
            'mautic_core_form_action' => array(
                'path'       => '/action/{objectAction}/{objectModel}/{objectId}',
                'controller' => 'MauticCoreBundle:Form:execute',
                'defaults'   => array(
                    'objectModel' => ''
                )
            )
        ),
        'public' => array(
            'mautic_js'                    => array(
                'path'       => '/mtc.js',
                'controller' => 'MauticCoreBundle:Js:index'
            ),
            'mautic_base_index'            => array(
                'path'       => '/',
                'controller' => 'MauticCoreBundle:Default:index'
            ),
            'mautic_secure_root'           => array(
                'path'       => '/s',
                'controller' => 'MauticCoreBundle:Default:redirectSecureRoot'
            ),
            'mautic_secure_root_slash'     => array(
                'path'       => '/s/',
                'controller' => 'MauticCoreBundle:Default:redirectSecureRoot'
            ),
            'mautic_remove_trailing_slash' => array(
                'path'         => '/{url}',
                'controller'   => 'MauticCoreBundle:Common:removeTrailingSlash',
                'method'       => 'GET',
                'requirements' => array(
                    'url' => '.*/$'
                )
            ),
            'mautic_public_bc_redirect'    => array(
                'path'         => '/p/{url}',
                'controller'   => 'MauticCoreBundle:Default:publicBcRedirect',
                'requirements' => array(
                    'url' => '.+'
                )
            ),
            'mautic_ajax_bc_redirect'      => array(
                'path'         => '/ajax{url}',
                'controller'   => 'MauticCoreBundle:Default:ajaxBcRedirect',
                'requirements' => array(
                    'url' => '.+'
                ),
                'defaults'     => array(
                    'url' => ''
                )
            ),
            'mautic_update_bc_redirect'    => array(
                'path'       => '/update',
                'controller' => 'MauticCoreBundle:Default:updateBcRedirect'
            )
        )
    ),
    'menu'       => array(
        'main'  => array(
            'mautic.core.components' => array(
                'id'        => 'mautic_components_root',
                'iconClass' => 'fa-puzzle-piece',
                'priority'  => 60
            ),
            'mautic.core.channels' => array(
                'id'        => 'mautic_channels_root',
                'iconClass' => 'fa-rss',
                'priority'  => 40
            )
        ),
        'admin' => array(
            'priority' => -1000,
            'items'    => array(
                'name'     => 'admin',
                'children' => array()
            )
        )
    ),
    'services'   => array(
        'events'  => array(
            'mautic.core.subscriber'              => array(
                'class' => 'Mautic\CoreBundle\EventListener\CoreSubscriber'
            ),
            'mautic.core.auditlog.subscriber'     => array(
                'class' => 'Mautic\CoreBundle\EventListener\AuditLogSubscriber'
            ),
            'mautic.core.configbundle.subscriber' => array(
                'class' => 'Mautic\CoreBundle\EventListener\ConfigSubscriber'
            ),
            'mautic.webpush.js.subscriber'           => array(
                'class' => 'Mautic\CoreBundle\EventListener\BuildJsSubscriber'
            ),
            'mautic.core.dashboard.subscriber'    => array(
                'class' => 'Mautic\CoreBundle\EventListener\DashboardSubscriber'
            )
        ),
        'forms'   => array(
            'mautic.form.type.spacer'             => array(
                'class' => 'Mautic\CoreBundle\Form\Type\SpacerType',
                'alias' => 'spacer'
            ),
            'mautic.form.type.tel'                => array(
                'class' => 'Mautic\CoreBundle\Form\Type\TelType',
                'alias' => 'tel'
            ),
            'mautic.form.type.button_group'       => array(
                'class' => 'Mautic\CoreBundle\Form\Type\ButtonGroupType',
                'alias' => 'button_group'
            ),
            'mautic.form.type.yesno_button_group' => array(
                'class' => 'Mautic\CoreBundle\Form\Type\YesNoButtonGroupType',
                'alias' => 'yesno_button_group'
            ),
            'mautic.form.type.standalone_button'  => array(
                'class' => 'Mautic\CoreBundle\Form\Type\StandAloneButtonType',
                'alias' => 'standalone_button'
            ),
            'mautic.form.type.form_buttons'       => array(
                'class' => 'Mautic\CoreBundle\Form\Type\FormButtonsType',
                'alias' => 'form_buttons'
            ),
            'mautic.form.type.hidden_entity'      => array(
                'class'     => 'Mautic\CoreBundle\Form\Type\HiddenEntityType',
                'alias'     => 'hidden_entity',
                'arguments' => 'doctrine.orm.entity_manager'
            ),
            'mautic.form.type.sortablelist'        => array(
                'class' => 'Mautic\CoreBundle\Form\Type\SortableListType',
                'alias' => 'sortablelist'
            ),
            'mautic.form.type.dynamiclist'         => array(
                'class' => 'Mautic\CoreBundle\Form\Type\DynamicListType',
                'alias' => 'dynamiclist'
            ),
            'mautic.form.type.coreconfig'         => array(
                'class'     => 'Mautic\CoreBundle\Form\Type\ConfigType',
                'arguments' => array(
                    'translator',
                    'mautic.helper.language',
                    'mautic.ip_lookup.factory',
                    '%mautic.supported_languages%',
                    '%mautic.ip_lookup_services%',
                    'mautic.ip_lookup'
                ),
                'alias'     => 'coreconfig'
            ),
            'mautic.form.type.coreconfig.iplookup_download_data_store_button' => array(
                'class'     => 'Mautic\CoreBundle\Form\Type\IpLookupDownloadDataStoreButtonType',
                'alias'     => 'iplookup_download_data_store_button',
                'arguments' => array(
                    'mautic.helper.template.date',
                    'translator'
                )
            ),
            'mautic.form.type.theme_list'         => array(
                'class'     => 'Mautic\CoreBundle\Form\Type\ThemeListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'theme_list'
            ),
            'mautic.form.type.daterange'          => array(
                'class'     => 'Mautic\CoreBundle\Form\Type\DateRangeType',
                'arguments' => 'mautic.factory',
                'alias'     => 'daterange'
            )
        ),
        'helpers' => array(
            'mautic.helper.menu'               => array(
                'class'     => 'Mautic\CoreBundle\Menu\MenuHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'menu_helper'
            ),
            'mautic.helper.template.date'      => array(
                'class'     => 'Mautic\CoreBundle\Templating\Helper\DateHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'date'
            ),
            'mautic.helper.template.exception' => array(
                'class'     => 'Mautic\CoreBundle\Templating\Helper\ExceptionHelper',
                'arguments' => '%kernel.root_dir%',
                'alias'     => 'exception'
            ),
            'mautic.helper.template.gravatar'  => array(
                'class'     => 'Mautic\CoreBundle\Templating\Helper\GravatarHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'gravatar'
            ),
            'mautic.helper.template.analytics' => array(
                'class'     => 'Mautic\CoreBundle\Templating\Helper\AnalyticsHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'analytics'
            ),
            'mautic.helper.template.mautibot'  => array(
                'class' => 'Mautic\CoreBundle\Templating\Helper\MautibotHelper',
                'alias' => 'mautibot'
            ),
            'mautic.helper.template.canvas'    => array(
                'class'     => 'Mautic\CoreBundle\Templating\Helper\SidebarCanvasHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'canvas'
            ),
            'mautic.helper.template.button'    => array(
                'class'     => 'Mautic\CoreBundle\Templating\Helper\ButtonHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'buttons'
            ),
            'mautic.helper.template.formatter' => array(
                'class'     => 'Mautic\CoreBundle\Templating\Helper\FormatterHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'formatter'
            ),
            'mautic.helper.template.security'  => array(
                'class'     => 'Mautic\CoreBundle\Templating\Helper\SecurityHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'security'
            ),
        ),
        'other'   => array(
            // Error handler
            'mautic.core.errorhandler.subscriber' => array(
                'class'     => 'Mautic\CoreBundle\EventListener\ErrorHandlingListener',
                'arguments' => array(
                    '%kernel.environment%',
                    'monolog.logger.mautic'
                ),
                'tag' => 'kernel.event_subscriber'
            ),

            // Configurator (used in installer and managing global config)
            'mautic.configurator' => array(
                'class'     => 'Mautic\InstallBundle\Configurator\Configurator', // In 2.0 change this to reference the CoreBundle
                'arguments' => array(
                    'mautic.factory'
                )
            ),

            // Template helper overrides
            'templating.helper.assets.class'     => 'Mautic\CoreBundle\Templating\Helper\AssetsHelper',
            'templating.helper.slots.class'      => 'Mautic\CoreBundle\Templating\Helper\SlotsHelper',
            'templating.name_parser.class'       => 'Mautic\CoreBundle\Templating\TemplateNameParser',
            'templating.helper.form.class'       => 'Mautic\CoreBundle\Templating\Helper\FormHelper',
            // Translator overrides
            'translator.class'                   => 'Mautic\CoreBundle\Translation\Translator',
            'templating.helper.translator.class' => 'Mautic\CoreBundle\Templating\Helper\TranslatorHelper',
            // System uses
            'mautic.factory'                     => array(
                'class'     => 'Mautic\CoreBundle\Factory\MauticFactory',
                'arguments' => 'service_container'
            ),
            'mautic.templating.name_parser'      => array(
                'class'     => 'Mautic\CoreBundle\Templating\TemplateNameParser',
                'arguments' => 'kernel'
            ),
            'mautic.route_loader'                => array(
                'class'     => 'Mautic\CoreBundle\Loader\RouteLoader',
                'arguments' => 'mautic.factory',
                'tag'       => 'routing.loader'
            ),
            'mautic.security'                    => array(
                'class'     => 'Mautic\CoreBundle\Security\Permissions\CorePermissions',
                'arguments' => 'mautic.factory'
            ),
            'mautic.translation.loader'          => array(
                'class'     => 'Mautic\CoreBundle\Loader\TranslationLoader',
                'arguments' => 'mautic.factory',
                'tag'       => 'translation.loader',
                'alias'     => 'mautic'
            ),
            'mautic.tblprefix_subscriber'        => array(
                'class' => 'Mautic\CoreBundle\EventListener\DoctrineEventsSubscriber',
                'tag'   => 'doctrine.event_subscriber'
            ),
            'mautic.exception.listener'          => array(
                'class'        => 'Mautic\CoreBundle\EventListener\ExceptionListener',
                'arguments'    => array(
                    '"MauticCoreBundle:Exception:show"',
                    'monolog.logger.mautic'
                ),
                'tag'          => 'kernel.event_listener',
                'tagArguments' => array(
                    'event'    => 'kernel.exception',
                    'method'   => 'onKernelException',
                    'priority' => 255
                )
            ),
            'transifex'                          => array(
                'class'     => 'BabDev\Transifex\Transifex',
                'arguments' => array(
                    array(
                        'api.username' => '%mautic.transifex_username%',
                        'api.password' => '%mautic.transifex_password%'
                    )
                )
            ),
            // Helpers
            'mautic.helper.assetgeneration'      => array(
                'class'     => 'Mautic\CoreBundle\Helper\AssetGenerationHelper',
                'arguments' => 'mautic.factory'
            ),
            'mautic.helper.cookie'               => array(
                'class'     => 'Mautic\CoreBundle\Helper\CookieHelper',
                'arguments' => 'mautic.factory'
            ),
            'mautic.helper.update'               => array(
                'class'     => 'Mautic\CoreBundle\Helper\UpdateHelper',
                'arguments' => 'mautic.factory'
            ),
            'mautic.helper.cache'                => array(
                'class'     => 'Mautic\CoreBundle\Helper\CacheHelper',
                'arguments' => 'mautic.factory'
            ),
            'mautic.helper.theme'                => array(
                'class'     => 'Mautic\CoreBundle\Helper\ThemeHelper',
                'arguments' => 'mautic.factory'
            ),
            'mautic.helper.encryption'           => array(
                'class'     => 'Mautic\CoreBundle\Helper\EncryptionHelper',
                'arguments' => 'mautic.factory'
            ),
            'mautic.helper.language'             => array(
                'class'     => 'Mautic\CoreBundle\Helper\LanguageHelper',
                'arguments' => 'mautic.factory'
            ),
            'mautic.helper.url'           => array(
                'class'     => 'Mautic\CoreBundle\Helper\UrlHelper',
                'arguments' => array(
                    'mautic.http.connector',
                    '%mautic.link_shortener_url%',
                    'monolog.logger.mautic',
                )
            ),
            // Menu
            'mautic.menu_renderer'               => array(
                'class'     => 'Mautic\CoreBundle\Menu\MenuRenderer',
                'arguments' => array(
                    'knp_menu.matcher',
                    'mautic.factory',
                    '%kernel.charset%'
                ),
                'tag'       => 'knp_menu.renderer',
                'alias'     => 'mautic'
            ),
            'mautic.menu.builder'                => array(
                'class'     => 'Mautic\CoreBundle\Menu\MenuBuilder',
                'arguments' => array(
                    'knp_menu.factory',
                    'knp_menu.matcher',
                    'mautic.factory'
                )
            ),
            'mautic.menu.main'                   => array(
                'class'   => 'Knp\Menu\MenuItem',
                'factory' => array('@mautic.menu.builder', 'mainMenu'),
                'tag'     => 'knp_menu.menu',
                'alias'   => 'main',
            ),
            'mautic.menu.admin'                  => array(
                'class'   => 'Knp\Menu\MenuItem',
                'factory' => array('@mautic.menu.builder', 'adminMenu'),
                'tag'     => 'knp_menu.menu',
                'alias'   => 'admin',
            ),
            // IP Lookup
            'mautic.ip_lookup.factory' => array(
                'class'     => 'Mautic\CoreBundle\Factory\IpLookupFactory',
                'arguments' => array(
                    '%mautic.ip_lookup_services%',
                    'monolog.logger.mautic',
                    'mautic.http.connector',
                    '%kernel.cache_dir%'
                )
            ),
            'mautic.ip_lookup' => array(
                'class'     => 'Mautic\CoreBundle\IpLookup\AbstractLookup', // bogus just to make cache compilation happy
                'factory'   => array('@mautic.ip_lookup.factory', 'getService'),
                'arguments' => array(
                    '%mautic.ip_lookup_service%',
                    '%mautic.ip_lookup_auth%',
                    '%mautic.ip_lookup_config%',
                    'mautic.http.connector'
                )
            ),
            // Other
            'mautic.http.connector' => array(
                'class'   => 'Joomla\Http\Http',
                'factory' => array('Joomla\Http\HttpFactory', 'getHttp')
            ),

            'twig.controller.exception.class'    => 'Mautic\CoreBundle\Controller\ExceptionController',
            'monolog.handler.stream.class'       => 'Mautic\CoreBundle\Monolog\Handler\PhpHandler',

            // Twig
            'templating.twig.extension.slot'    => array(
                'class' => 'Mautic\CoreBundle\Templating\Twig\Extension\SlotExtension',
                'arguments' => array(
                    'mautic.factory'
                ),
                'tag' => 'twig.extension'
            ),
            'templating.twig.extension.asset'    => array(
                'class' => 'Mautic\CoreBundle\Templating\Twig\Extension\AssetExtension',
                'arguments' => array(
                    'mautic.factory'
                ),
                'tag' => 'twig.extension'
            ),

        )
    ),

    'ip_lookup_services' => array(
        'freegeoip' => array(
            'display_name' => 'Freegeoip.net',
            'class'        => 'Mautic\CoreBundle\IpLookup\FreegeoipLookup'
        ),
        'geobytes' => array(
            'display_name' => 'Geobytes',
            'class'        => 'Mautic\CoreBundle\IpLookup\GeobytesLookup'
        ),
        'geoips' => array(
            'display_name' => 'GeoIPs',
            'class'        => 'Mautic\CoreBundle\IpLookup\GeoipsLookup'
        ),
        'ipinfodb' => array(
            'display_name' => 'IPInfoDB',
            'class'        => 'Mautic\CoreBundle\IpLookup\IpinfodbLookup'
        ),
        'maxmind_country' => array(
            'display_name' => 'MaxMind - Country Geolocation',
            'class'        => 'Mautic\CoreBundle\IpLookup\MaxmindCountryLookup'
        ),
        'maxmind_omni' => array(
            'display_name' => 'MaxMind - Insights (formerly Omni)',
            'class'        => 'Mautic\CoreBundle\IpLookup\MaxmindOmniLookup'
        ),
        'maxmind_precision' => array(
            'display_name' => 'MaxMind - GeoIP2 Precision',
            'class'        => 'Mautic\CoreBundle\IpLookup\MaxmindPrecisionLookup'
        ),
        'maxmind_download' => array(
            'display_name' => 'MaxMind - GeoLite2 City Download',
            'class'        => 'Mautic\CoreBundle\IpLookup\MaxmindDownloadLookup'
        ),
        'telize' => array(
            'display_name' => 'Telize',
            'class'        => 'Mautic\CoreBundle\IpLookup\TelizeLookup'
        ),
		'ip2loctionlocal'=>array(
		    'display_name' => 'IP2Location Local Bin File',
            'class'        => 'Mautic\CoreBundle\IpLookup\IP2LocationBinLookup'
        ),
		'ip2loctionapi'=>array(
		    'display_name' => 'IP2Location Web Service',
            'class'        => 'Mautic\CoreBundle\IpLookup\IP2LocationAPILookup'
        )
    ),

    'parameters' => array(
        'site_url'                       => '',
        'webroot'                        => '',
        'cache_path'                     => '%kernel.root_dir%/cache',
        'log_path'                       => '%kernel.root_dir%/logs',
        'image_path'                     => 'media/images',
        'theme'                          => 'Mauve',
        'db_driver'                      => 'pdo_mysql',
        'db_host'                        => 'localhost',
        'db_port'                        => 3306,
        'db_name'                        => '',
        'db_user'                        => '',
        'db_password'                    => '',
        'db_table_prefix'                => '',
        'db_path'                        => '',
        'locale'                         => 'en_US',
        'secret_key'                     => '',
        'trusted_hosts'                  => null,
        'trusted_proxies'                => null,
        'rememberme_key'                 => hash('sha1', uniqid(mt_rand())),
        'rememberme_lifetime'            => 31536000, //365 days in seconds
        'rememberme_path'                => '/',
        'rememberme_domain'              => '',
        'default_pagelimit'              => 30,
        'default_timezone'               => 'UTC',
        'date_format_full'               => 'F j, Y g:i a T',
        'date_format_short'              => 'D, M d',
        'date_format_dateonly'           => 'F j, Y',
        'date_format_timeonly'           => 'g:i a',
        'ip_lookup_service'              => 'maxmind_download',
        'ip_lookup_auth'                 => '',
        'ip_lookup_config'               => array(),
        'transifex_username'             => '',
        'transifex_password'             => '',
        'update_stability'               => 'stable',
        'cookie_path'                    => '/',
        'cookie_domain'                  => '',
        'cookie_secure'                  => null,
        'cookie_httponly'                => false,
        'do_not_track_ips'               => array(),
        'link_shortener_url'             => null,
        'cached_data_timeout'            => 10
    )
);
