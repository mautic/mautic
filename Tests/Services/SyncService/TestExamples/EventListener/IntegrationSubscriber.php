<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Services\SyncService\TestExamples\EventListener;

use MauticPlugin\IntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Event\SyncEvent;
use MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Tests\Services\SyncService\TestExamples\Facade\SyncDataExchange\ExampleSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Tests\Services\SyncService\TestExamples\Integration\ExampleIntegration;
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

        // Generate mapping manual that will be passed to the sync service. This instructs the sync service how to map Mautic fields to integration fields
        $mappingManual = new MappingManualDAO(ExampleIntegration::NAME);

        // Each object like lead, contact, user, company, account, etc, will need it's own ObjectMappingDAO
        // In this example, Mautic's Contact object is mapped to the Example's Lead object
        $leadObjectMapping = new ObjectMappingDAO(
            MauticSyncDataExchange::CONTACT_OBJECT,
            ExampleSyncDataExchange::LEAD_OBJECT
        );
        $mappingManual->addObjectMapping($leadObjectMapping);

        // Then it is also mapping Mautic's Contact object to the Example's Contact object
        $contactObjectMapping = new ObjectMappingDAO(
            MauticSyncDataExchange::CONTACT_OBJECT,
            ExampleSyncDataExchange::CONTACT_OBJECT
        );
        $mappingManual->addObjectMapping($contactObjectMapping);

        // Get field mapping as configured in Mautic's integration config
        $mappedFields = $this->getConfiguredFieldMapping();

        foreach ($mappedFields as $integrationField => $mauticField) {
            // In this case, we're just adding each field to each of the objects
            // Of course, other integrations may need more logic

            // The lead object will only sync from the integration to Mautic; it's also possible to set ObjectMappingDAO::SYNC_TO_INTEGRATION
            $leadObjectMapping->addFieldMapping($mauticField, $integrationField, ObjectMappingDAO::SYNC_TO_MAUTIC);

            // The contact object will sync by default, bidirectionally
            $contactObjectMapping->addFieldMapping($mauticField, $integrationField);
        }

        // Set the SyncDataExchangeInterface and MappingManualDAO for the sync service to execute
        $event->setSyncServices($this->dataExchange, $mappingManual);
    }

    /**
     * Likely will get this mapping out of the Integration's settings
     *
     * @return array
     */
    public function getConfiguredFieldMapping()
    {
        return [
            'first_name' => 'firstname',
            'last_name'  => 'lastname',
            'email'      => 'email'
        ];
    }
}
