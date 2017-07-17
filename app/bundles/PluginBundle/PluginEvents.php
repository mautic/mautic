<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle;

/**
 * Class PluginEvents.
 *
 * Events available for PluginEvents
 */
final class PluginEvents
{
    /**
     * The mautic.plugin_on_integration_config_save event is dispatched when an integration's configuration is saved.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_CONFIG_SAVE = 'mautic.plugin_on_integration_config_save';

    /**
     * The mautic.plugin_on_integration_keys_encrypt event is dispatched prior to encrypting keys to be stored into the database.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationKeyEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT = 'mautic.plugin_on_integration_keys_encrypt';

    /**
     * The mautic.plugin_on_integration_keys_decrypt event is dispatched after fetching and decrypting keys from the database.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationKeyEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_KEYS_DECRYPT = 'mautic.plugin_on_integration_keys_decrypt';

    /**
     * The mautic.plugin_on_integration_keys_merge event is dispatched after new keys are merged into existing ones.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationKeyEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_KEYS_MERGE = 'mautic.plugin_on_integration_keys_merge';

    /**
     * The mautic.plugin_on_integration_request event is dispatched before a request is made.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationRequestEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_REQUEST = 'mautic.plugin_on_integration_request';

    /**
     * The mautic.plugin_on_integration_response event is dispatched after a request is made.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationRequestEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_RESPONSE = 'mautic.plugin_on_integration_response';

    /**
     * The mautic.plugin_on_integration_auth_redirect event is dispatched when an authorization URL is generated and before the user is redirected to it.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationAuthRedirectEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_AUTH_REDIRECT = 'mautic.plugin_on_integration_auth_redirect';

    /**
     * The mautic.plugin.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.plugin.on_campaign_trigger_action';

    /**
     * The mautic.plugin_on_integration_get_auth_callback_url event is dispatched when generating the redirect/callback URL.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationAuthCallbackUrlEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_GET_AUTH_CALLBACK_URL = 'mautic.plugin_on_integration_get_auth_callback_url';

    /**
     * The mautic.plugin_on_integration_form_display event is dispatched when fetching display settings for the integration's config form.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationFormDisplayEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_FORM_DISPLAY = 'mautic.plugin_on_integration_form_display';

    /**
     * The mautic.plugin_on_integration_form_build event is dispatched when building an integration's config form.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationFormBuildEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_FORM_BUILD = 'mautic.plugin_on_integration_form_build';
}
