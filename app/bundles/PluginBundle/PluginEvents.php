<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle;

/**
 * Class PluginEvents
 *
 * Events available for PluginEvents
 */
final class PluginEvents
{

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
     * The mautic.plugin_on_integration_auth_redirect event is dispatched when an authorization URL is generated and before the user is redirected to it
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationAuthRedirectEvent instance.
     *
     * @var string
     */
    const PLUGIN_ON_INTEGRATION_AUTH_REDIRECT = 'mautic.plugin_on_integration_auth_redirect';

}
