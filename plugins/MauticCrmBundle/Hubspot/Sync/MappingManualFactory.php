<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;

class MappingManualFactory
{
    /**
     * The Mautic object each HubSpot object syncs with. Companies come first so that a contact's company is linked
     * by the time contacts sync.
     */
    public const array MAUTIC_OBJECTS = [
        HubspotClient::COMPANIES => Company::NAME,
        HubspotClient::CONTACTS  => Contact::NAME,
    ];

    private ?MappingManualDAO $manual = null;

    public function __construct(
        private readonly Config $config,
        private readonly FieldRepository $fieldRepository,
    ) {
    }

    public function getManual(): MappingManualDAO
    {
        if (null !== $this->manual) {
            return $this->manual;
        }

        $enabledObjects = $this->config->getEnabledObjects();
        $objectMappings = [];

        foreach (self::MAUTIC_OBJECTS as $object => $mauticObject) {
            if (!in_array($object, $enabledObjects, true)) {
                continue;
            }

            $fields        = $this->fieldRepository->getFields($object);
            $objectMapping = new ObjectMappingDAO($mauticObject, $object);
            foreach ($this->config->getFieldMappings($object) as $property => $mapping) {
                if (!isset($fields[$property])) {
                    // The property no longer exists in HubSpot
                    continue;
                }

                // A property HubSpot does not let us write only syncs to Mautic, whatever was saved
                $direction = $fields[$property]->isWritable() ? $mapping['syncDirection'] : ObjectMappingDAO::SYNC_TO_MAUTIC;
                $objectMapping->addFieldMapping($mapping['mappedField'], $property, $direction, $fields[$property]->isRequired());
            }

            $objectMappings[] = $objectMapping;
        }

        return $this->manual = new MappingManualDAO(HubspotIntegration::NAME, $objectMappings);
    }
}
