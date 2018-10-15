<?php

namespace MauticPlugin\IntegrationsBundle;

final class SyncEvents
{
    public const INTEGRATION_POST_EXECUTE = 'mautic.sync_post_execute_integration';

    /**
     * The mautic.integration.config_form_loaded event is dispatched when config page for integration is loaded.
     *
     * The event listener receives a
     * Mautic\IntegrationsBundle\Event\FormLoadEvent instance.
     *
     * @var string
     */
    public const INTEGRATION_CONFIG_FORM_LOAD = 'mautic.integration.config_form_loaded';
}
