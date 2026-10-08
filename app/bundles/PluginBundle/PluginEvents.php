<?php

declare(strict_types=1);

namespace Mautic\PluginBundle;

final class PluginEvents
{
    /**
     * The mautic.plugin_on_integration_keys_encrypt event is dispatched prior to encrypting keys to be stored into the database.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationKeyEvent instance.
     */
    public const string PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT = 'mautic.plugin_on_integration_keys_encrypt';

    /**
     * The mautic.plugin_on_integration_keys_decrypt event is dispatched after fetching and decrypting keys from the database.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationKeyEvent instance.
     */
    public const string PLUGIN_ON_INTEGRATION_KEYS_DECRYPT = 'mautic.plugin_on_integration_keys_decrypt';

    /**
     * The mautic.plugin_on_integration_keys_merge event is dispatched after new keys are merged into existing ones.
     *
     * The event listener receives a Mautic\PluginBundle\Event\PluginIntegrationKeyEvent instance.
     */
    public const string PLUGIN_ON_INTEGRATION_KEYS_MERGE = 'mautic.plugin_on_integration_keys_merge';

    /**
     * The mautic.plugin.on_campaign_batch_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_BATCH_ACTION = 'mautic.plugin.on_campaign_batch_action';
}
