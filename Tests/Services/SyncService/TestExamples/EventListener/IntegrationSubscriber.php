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
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\FieldMappingDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\MauticIntegrationsBundle\Event\SyncEvent;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService\MauticSyncDataExchange;
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

        // Generate mapping manual that will be passed to the sync service. This instructs the sync service how to map Mautic fields to integration fields
        $mappingManual = new MappingManualDAO();

        // Each object like lead, contact, user, company, account, etc, will need it's own ObjectMappingDAO
        // In this example, Mautic's Contact object is mapped to the Example's Lead object
        $leadObjectMapping = new ObjectMappingDAO(MauticSyncDataExchange::CONTACT_OBJECT, ExampleSyncDataExchange::LEAD_OBJECT);

        // Then it is also mapping Mautic's Contact object to the Example's Contact object
        $contactObjectMapping = new ObjectMappingDAO(MauticSyncDataExchange::CONTACT_OBJECT, ExampleSyncDataExchange::CONTACT_OBJECT);

        // Get field metadata from a service/API
        $fieldMetadata = ExampleSyncDataExchange::FIELDS;

        // Get field mapping as configured in Mautic's integration config
        $mappedFields = $this->getConfiguredFieldMapping();

        foreach ($mappedFields as $integrationField => $mauticField) {
            $leadObjectMapping->addFieldMapping($mauticField, $integrationField);
            $contactObjectMapping->addFieldMapping($mauticField, $integrationField);
        }
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
