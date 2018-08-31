<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle;

/**
 * Events available for IntegrationEvents
 */
final class IntegrationEvents
{
    /**
     * The mautic.integration.on_sync_triggered event is dispatched when an integration sync is triggered.
     *
     * The event listener receives a MauticPlugin\IntegrationsBundle\SyncEvent instance.
     *
     * @var string
     */
    const ON_SYNC_TRIGGERED = 'mautic.integration.on_sync_triggered';

    /**
     * The mautic.integration.on_sync_complete event is dispatched when an integration sync has completed in order to give opportunity for cleanup.
     *
     * The event listener receives a MauticPlugin\IntegrationsBundle\SyncEvent instance.
     *
     * @var string
     */
    const ON_SYNC_COMPLETE = 'mautic.integration.on_sync_complete';
}
