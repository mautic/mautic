<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Tests\Services\SyncService\TestExamples\EventListener;

use MauticPlugin\MagentoBundle\Integration\ExampleIntegration;
use MauticPlugin\MauticIntegrationsBundle\Event\SyncEvent;
use MauticPlugin\MauticIntegrationsBundle\IntegrationEvents;
use MauticPlugin\MauticIntegrationsBundle\Tests\Services\SyncService\TestExamples\Facade\SyncDataExchange\ExampleSyncDataExchange;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IntegrationSubscriber implements EventSubscriberInterface
{
    /**
     * @var ExampleSyncDataExchange
     */
    private $dataExchange;

    /**
     * IntegrationSubscriber constructor.
     *
     * @param ExampleSyncDataExchange $dataExchange
     */
    public function __construct(ExampleSyncDataExchange $dataExchange)
    {
        $this->dataExchange = $dataExchange;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            IntegrationEvents::ON_SYNC_TRIGGERED => ['onSync', 0],
        ];
    }

    /**
     * @param SyncEvent $event
     */
    public function onSync(SyncEvent $event)
    {
        if (!$event->shouldIntegrationSync(ExampleIntegration::NAME)) {

            return;
        }

        // Build MappingManual
    }
}
