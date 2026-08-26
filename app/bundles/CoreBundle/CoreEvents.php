<?php

declare(strict_types=1);

namespace Mautic\CoreBundle;

final class CoreEvents
{
    /**
     * The mautic.build_menu event is thrown to render menu items.
     *
     * The event listener receives a Mautic\CoreBundle\Event\MenuEvent instance.
     */
    public const string BUILD_MENU = 'mautic.build_menu';

    /**
     * The mautic.build_route event is thrown to build Mautic bundle routes.
     *
     * The event listener receives a Mautic\CoreBundle\Event\RouteEvent instance.
     */
    public const string BUILD_ROUTE = 'mautic.build_route';

    /**
     * The mautic.global_search event is thrown to build global search results from applicable bundles.
     *
     * The event listener receives a Mautic\CoreBundle\Event\GlobalSearchEvent instance.
     */
    public const string GLOBAL_SEARCH = 'mautic.global_search';

    /**
     * The mautic.list_stats event is thrown to build statistical results from applicable bundles/database tables.
     *
     * The event listener receives a Mautic\CoreBundle\Event\StatsEvent instance.
     */
    public const string LIST_STATS = 'mautic.list_stats';

    /**
     * The mautic.build_command_list event is thrown to build global search's autocomplete list.
     *
     * The event listener receives a Mautic\CoreBundle\Event\CommandListEvent instance.
     */
    public const string BUILD_COMMAND_LIST = 'mautic.build_command_list';

    /**
     * The mautic.on_fetch_icons event is thrown to fetch icons of menu items.
     *
     * The event listener receives a Mautic\CoreBundle\Event\IconEvent instance.
     */
    public const string FETCH_ICONS = 'mautic.on_fetch_icons';

    /**
     * The mautic.pre_upgrade is dispatched before an upgrade.
     *
     * The event listener receives a Mautic\CoreBundle\Event\UpgradeEvent instance.
     */
    public const string PRE_UPGRADE = 'mautic.pre_upgrade';

    /**
     * The mautic.post_upgrade is dispatched after an upgrade.
     *
     * The event listener receives a Mautic\CoreBundle\Event\UpgradeEvent instance.
     */
    public const string POST_UPGRADE = 'mautic.post_upgrade';

    /**
     * The mautic.build_embeddable_js event is dispatched to allow plugins to extend the mautic tracking js.
     *
     * The event listener receives a Mautic\CoreBundle\Event\BuildJsEvent instance.
     */
    public const string BUILD_MAUTIC_JS = 'mautic.build_embeddable_js';

    /**
     * The mautic.maintenance_cleanup_data event is dispatched to purge old data.
     *
     * The event listener receives a Mautic\CoreBundle\Event\MaintenanceEvent instance.
     */
    public const string MAINTENANCE_CLEANUP_DATA = 'mautic.maintenance_cleanup_data';

    /**
     * The mautic.view_inject_custom_buttons event is dispatched to inject custom buttons into Mautic's UI by plugins/other bundles.
     *
     * The event listener receives a Mautic\CoreBundle\Event\CustomButtonEvent instance.
     */
    public const string VIEW_INJECT_CUSTOM_BUTTONS = 'mautic.view_inject_custom_buttons';

    /**
     * The mautic.view_inject_custom_content event is dispatched by views to collect custom content to be injected in UIs.
     *
     * The event listener receives a Mautic\CoreBundle\Event\CustomContentEvent instance.
     */
    public const string VIEW_INJECT_CUSTOM_CONTENT = 'mautic.view_inject_custom_content';

    /**
     * The mautic.view_inject_custom_template event is dispatched when a template is to be rendered giving opportunity to change template or
     * vars.
     *
     * The event listener receives a Mautic\CoreBundle\Event\CustomTemplateEvent instance.
     */
    public const string VIEW_INJECT_CUSTOM_TEMPLATE = 'mautic.view_inject_custom_template';

    /**
     * The mautic.view_inject_custom_assets event is dispatched when assets are rendered.
     *
     * The event listener receives a Mautic\CoreBundle\Event\CustomAssetsEvent instance.
     */
    public const string VIEW_INJECT_CUSTOM_ASSETS = 'mautic.view_inject_custom_assets';

    /**
     * The mautic.on_generated_columns_build event is dispatched when a list of generated columns is being built.
     *
     * The event listener receives a Mautic\CoreBundle\Event\GeneratedColumnsEvent instance.
     */
    public const string ON_GENERATED_COLUMNS_BUILD = 'mautic.on_generated_columns_build';
}
