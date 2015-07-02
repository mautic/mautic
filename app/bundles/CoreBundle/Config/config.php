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
        'main' => array(
            'mautic_core_ajax'             => array(
                'path'       => '/ajax',
                'controller' => 'MauticCoreBundle:Ajax:delegateAjax'
            ),
            'mautic_core_update'           => array(
                'path'       => '/update',
                'controller' => 'MauticCoreBundle:Update:index'
            ),
            'mautic_core_form_action'      => array(
                'path'       => '/action/{objectAction}/{objectModel}/{objectId}',
                'controller' => 'MauticCoreBundle:Form:execute',
                'defaults'   => array(
                    'objectModel' => ''
                )
            )
        ),
        'public' => array(
            'mautic_base_index' => array(
                'path' => '/',
                'controller'   => 'MauticCoreBundle:Default:index'
            ),
            'mautic_secure_root' => array(
                'path'         => '/s',
                'controller'   => 'MauticCoreBundle:Default:redirectSecureRoot'
            ),
            'mautic_secure_root_slash' => array(
                'path'         => '/s/',
                'controller'   => 'MauticCoreBundle:Default:redirectSecureRoot'
            ),
            'mautic_remove_trailing_slash' => array(
                'path'         => '/{url}',
                'controller'   => 'MauticCoreBundle:Common:removeTrailingSlash',
                'method'       => 'GET',
                'requirements' => array(
                    'url' => '.*/$'
                )
            ),
            'mautic_public_bc_redirect' => array(
                'path'         => '/p/{url}',
                'controller'   => 'MauticCoreBundle:Default:publicBcRedirect',
                'requirements' => array(
                    'url' => '.+'
                )
            ),
            'mautic_ajax_bc_redirect' => array(
                'path'         => '/ajax{url}',
                'controller'   => 'MauticCoreBundle:Default:ajaxBcRedirect',
                'requirements' => array(
                    'url' => '.+'
                ),
                'defaults' =>  array(
                    'url' => ''
                )
            ),
            'mautic_update_bc_redirect' => array(
                'path'         => '/update',
                'controller'   => 'MauticCoreBundle:Default:updateBcRedirect'
            )
        )
    ),

    'menu'       => array(
        'main'  => array(
            'priority' => -1000,
            'items'    => array(
                'name'     => 'root',
                'children' => array()
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
            'mautic.form.type.list'               => array(
                'class' => 'Mautic\CoreBundle\Form\Type\SortableListType',
                'alias' => 'sortablelist'
            ),
            'mautic.form.type.coreconfig'         => array(
                'class'     => 'Mautic\CoreBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.factory',
                'alias'     => 'coreconfig'
            ),
            'mautic.form.type.theme_list'         => array(
                'class'     => 'Mautic\CoreBundle\Form\Type\ThemeListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'theme_list'
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
            'mautic.helper.template.analytics'  => array(
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
            'mautic.helper.template.security' => array(
                'class'     => 'Mautic\CoreBundle\Templating\Helper\SecurityHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'security'
            )
        ),
        'other'   => array(
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

            // Mailers
            'mautic.transport.amazon'            => array(
                'class'        => 'Mautic\CoreBundle\Swiftmailer\Transport\AmazonTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'methodCalls'  => array(
                    'setUsername' => array('%mautic.mailer_user%'),
                    'setPassword' => array('%mautic.mailer_password%')
                )
            ),
            'mautic.transport.mandrill'          => array(
                'class'        => 'Mautic\CoreBundle\Swiftmailer\Transport\MandrillTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'methodCalls'  => array(
                    'setUsername'      => array('%mautic.mailer_user%'),
                    'setPassword'      => array('%mautic.mailer_password%'),
                    'setMauticFactory' => array('mautic.factory')
                )
            ),
            'mautic.transport.sendgrid'          => array(
                'class'        => 'Mautic\CoreBundle\Swiftmailer\Transport\SendgridTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'methodCalls'  => array(
                    'setUsername' => array('%mautic.mailer_user%'),
                    'setPassword' => array('%mautic.mailer_password%')
                )
            ),
            'mautic.transport.postmark'          => array(
                'class'        => 'Mautic\CoreBundle\Swiftmailer\Transport\PostmarkTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'methodCalls'  => array(
                    'setUsername' => array('%mautic.mailer_user%'),
                    'setPassword' => array('%mautic.mailer_password%')
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
                'class'          => 'Knp\Menu\MenuItem',
                'factoryService' => 'mautic.menu.builder',
                'factoryMethod'  => 'mainMenu',
                'tag'            => 'knp_menu.menu',
                'alias'          => 'main'
            ),
            'mautic.menu.admin'                  => array(
                'class'          => 'Knp\Menu\MenuItem',
                'factoryService' => 'mautic.menu.builder',
                'factoryMethod'  => 'adminMenu',
                'tag'            => 'knp_menu.menu',
                'alias'          => 'admin'
            ),
            'twig.controller.exception.class'    => 'Mautic\CoreBundle\Controller\ExceptionController',
            'monolog.handler.stream.class'       => 'Mautic\CoreBundle\Monolog\Handler\PhpHandler'
        )
    ),

    'parameters' => array(
        'site_url'                     => '',
        'webroot'                      => '',
        'cache_path'                   => '%kernel.root_dir%/cache',
        'log_path'                     => '%kernel.root_dir%/logs',
        'image_path'                   => 'media/images',
        'theme'                        => 'Mauve',
        'db_driver'                    => 'pdo_mysql',
        'db_host'                      => 'localhost',
        'db_port'                      => 3306,
        'db_name'                      => '',
        'db_user'                      => '',
        'db_password'                  => '',
        'db_table_prefix'              => '',
        'db_path'                      => '',
        'mailer_from_name'             => 'Mautic',
        'mailer_from_email'            => 'email@yoursite.com',
        'mailer_transport'             => 'mail',
        'mailer_host'                  => '',
        'mailer_port'                  => null,
        'mailer_user'                  => null,
        'mailer_password'              => null,
        'mailer_encryption'            => null, //tls or ssl,
        'mailer_auth_mode'             => null, //plain, login or cram-md5
        'mailer_spool_type'            => 'memory', //memory = immediate; file = queue
        'mailer_spool_path'            => '%kernel.root_dir%/spool',
        'mailer_spool_msg_limit'       => null,
        'mailer_spool_time_limit'      => null,
        'mailer_spool_recover_timeout' => 900,
        'mailer_spool_clear_timeout'   => 1800,
        'locale'                       => 'en_US',
        'secret_key'                   => '',
        'trusted_hosts'                => null,
        'trusted_proxies'              => null,
        'rememberme_key'               => hash('sha1', uniqid(mt_rand())),
        'rememberme_lifetime'          => 31536000, //365 days in seconds
        'rememberme_path'              => '/',
        'rememberme_domain'            => '',
        'default_pagelimit'            => 30,
        'default_timezone'             => 'UTC',
        'date_format_full'             => 'F j, Y g:i a T',
        'date_format_short'            => 'D, M d',
        'date_format_dateonly'         => 'F j, Y',
        'date_format_timeonly'         => 'g:i a',
        'ip_lookup_service'            => 'telize',
        //telize (free with no limit at this time)
        //freegeoip (free with 10000/hr limit)
        //geobytes ( free 20/hr limit or paid account restricted to calls from single IP
        //ipinfodb (paid; api key required)
        //geoips (paid; api key required)
        //maxmind_country, maxmind_precision, or maxmind_omni (paid; username/license key required)
        'ip_lookup_auth'               => '',
        'transifex_username'           => '',
        'transifex_password'           => '',
        'update_stability'             => 'stable',
        'cookie_path'                  => '/',
        'cookie_domain'                => '',
        'cookie_secure'                => null,
        'cookie_httponly'              => false,
    )
);
