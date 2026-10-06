<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle;

final class IntegrationEvents
{
    /**
     * The mautic.integration.sync_post_execute_integration event is dispatched after a sync is executed.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\SyncEvent object.
     */
    public const string INTEGRATION_POST_EXECUTE = 'mautic.integration.sync_post_execute_integration';

    /**
     * The mautic.integration.config_form_loaded event is dispatched when config page for integration is loaded.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\FormLoadEvent object.
     */
    public const string INTEGRATION_CONFIG_FORM_LOAD = 'mautic.integration.config_form_loaded';

    /**
     * The mautic.integration.config_before_save event is dispatched prior to an integration's configuration is saved.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\ConfigAuthUrlEvent instance.
     */
    public const string INTEGRATION_CONFIG_ON_GENERATE_AUTH_URL = 'mautic.integration.INTEGRATION_CONFIG_ON_GENERATE_AUTH_URL';

    /**
     * The mautic.integration.keys_before_encryption event is dispatched prior to encrypting keys to be stored into the database.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\KeysEncryptionEvent instance.
     */
    public const string INTEGRATION_KEYS_BEFORE_ENCRYPTION = 'mautic.integration.keys_before_encryption';

    /**
     * The mautic.integration.keys_after_decryption event is dispatched after fetching and decrypting keys from the database.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\KeysDecryptionEvent instance.
     */
    public const string INTEGRATION_KEYS_AFTER_DECRYPTION = 'mautic.integration.keys_after_decryption';

    /**
     * The mautic.integration.mautic_sync_field_load event is dispatched when Mautic sync fields are build.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\MauticSyncFieldsLoadEvent instance.
     */
    public const string INTEGRATION_MAUTIC_SYNC_FIELDS_LOAD = 'mautic.integration.mautic_sync_field_load';

    /**
     * The mautic.integration.INTEGRATION_COLLECT_INTERNAL_OBJECTS event is dispatched when a list of Mautic internal objects is build.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\InternalObjectEvent instance.
     */
    public const string INTEGRATION_COLLECT_INTERNAL_OBJECTS = 'mautic.integration.INTEGRATION_COLLECT_INTERNAL_OBJECTS';

    /**
     * The mautic.integration.INTEGRATION_CREATE_INTERNAL_OBJECTS event is dispatched when a list of Mautic internal objects should be created.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\InternalObjectCreateEvent instance.
     */
    public const string INTEGRATION_CREATE_INTERNAL_OBJECTS = 'mautic.integration.INTEGRATION_CREATE_INTERNAL_OBJECTS';

    /**
     * The mautic.integration.INTEGRATION_UPDATE_INTERNAL_OBJECTS event is dispatched when a list of Mautic internal objects should be updated.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\InternalObjectUpdateEvent instance.
     */
    public const string INTEGRATION_UPDATE_INTERNAL_OBJECTS = 'mautic.integration.INTEGRATION_UPDATE_INTERNAL_OBJECTS';

    /**
     * The mautic.integration.INTEGRATION_FIND_INTERNAL_RECORDS event is dispatched when a list of Mautic internal object records by ID is requested.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\InternalObjectFindEvent instance.
     */
    public const string INTEGRATION_FIND_INTERNAL_RECORDS = 'mautic.integration.INTEGRATION_FIND_INTERNAL_RECORDS';

    /**
     * The mautic.integration.INTEGRATION_FIND_OWNER_IDS event is dispatched when a list of Mautic internal owner IDs by internal object ID is requested.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\InternalObjectFindEvent instance.
     */
    public const string INTEGRATION_FIND_OWNER_IDS = 'mautic.integration.INTEGRATION_FIND_OWNER_IDS';

    /**
     * The mautic.integration.INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE event is dispatched when a Mautic internal object route is requested.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\InternalObjectOwnerEvent instance.
     */
    public const string INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE = 'mautic.integration.INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE';

    /**
     * This event is dispatched when a tokens are being built to represent links to mapped integration objects.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\MappedIntegrationObjectTokenEvent instance.
     */
    public const string INTEGRATION_OBJECT_TOKEN_EVENT = 'mautic.integration.INTEGRATION_OBJECT_TOKEN_EVENT';

    /**
     * The mautic.integration.INTEGRATION_FIND_INTERNAL_RECORD event is dispatched when a list of Mautic internal object record by ID is requested.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\InternalObjectFindByIdEvent instance.
     */
    public const string INTEGRATION_FIND_INTERNAL_RECORD = 'mautic.integration.INTEGRATION_FIND_INTERNAL_RECORD';

    /**
     * This event is dispatched when api keys is updated/inserted.
     *
     * The event listener receives a Mautic\IntegrationsBundle\Event\KeysSaveEvent instance.
     */
    public const string INTEGRATION_API_KEYS_BEFORE_SAVE = 'mautic.integration.INTEGRATION_API_KEYS_BEFORE_SAVE';
}
