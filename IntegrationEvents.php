<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle;

/**
 * Events available for IntegrationEvents
 */
final class IntegrationEvents
{
    /**
     * The mautic.integration.on_sync_triggered event is dispatched when an integration sych is triggerd.
     *
     * The event listener receives a MauticPlugin\MauticIntegrationsBundle\IntegrationEvents instance.
     *
     * @var string
     */
    const ON_SYNC_TRIGGERED = 'mautic.integration.on_sync_triggered';
}
