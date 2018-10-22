<?php

namespace MauticPlugin\IntegrationsBundle;

final class IntegrationEvents
{
    /**
     * The mautic.integration.sync_post_execute_integration event is dispatched after a sync is executed
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\SyncEvent object.
     *
     * @var string
     */
    public const INTEGRATION_POST_EXECUTE = 'mautic.integration.sync_post_execute_integration';

    /**
     * The mautic.integration.config_form_loaded event is dispatched when config page for integration is loaded.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\FormLoadEvent object.
     *
     * @var string
     */
    public const INTEGRATION_CONFIG_FORM_LOAD = 'mautic.integration.config_form_loaded';

    /**
     * The mautic.integration.config_before_save event is dispatched prior to an integration's configuration is saved.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\ConfigSaveEvent instance.
     *
     * @var string
     */
    public const INTEGRATION_CONFIG_BEFORE_SAVE = 'mautic.integration.config_before_save';

    /**
     * The mautic.integration.config_after_save event is dispatched after an integration's configuration is saved.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\ConfigSaveEvent instance.
     *
     * @var string
     */
    public const INTEGRATION_CONFIG_AFTER_SAVE = 'mautic.integration.config_after_save';

    /**
     * The mautic.integration.keys_before_encryption event is dispatched prior to encrypting keys to be stored into the database.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\KeysEncryptionEvent instance.
     *
     * @var string
     */
    public const INTEGRATION_KEYS_BEFORE_ENCRYPTION = 'mautic.integration.keys_before_encryption';

    /**
     * The mautic.integration.keys_after_decryption event is dispatched after fetching and decrypting keys from the database.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\KeysDecryptionEvent instance.
     *
     * @var string
     */
    public const INTEGRATION_KEYS_AFTER_DECRYPTION = 'mautic.integration.keys_after_decryption';
}
