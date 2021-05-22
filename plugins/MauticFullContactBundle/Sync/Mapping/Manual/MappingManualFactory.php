<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Sync\Mapping\Manual;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use MauticPlugin\MauticFullContactBundle\Sync\Mapping\Field\Field;
use MauticPlugin\MauticFullContactBundle\Sync\Mapping\Field\FieldRepository;

class MappingManualFactory
{
    private $object_name = FullContactIntegration::NAME;

    /**
     * @var MappingManualDAO
     */
    private $manual;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    public function __construct(FieldRepository $fieldRepository)
    {
        $this->fieldRepository = $fieldRepository;
    }

    public function getManual(): MappingManualDAO
    {
        if ($this->manual) {
            return $this->manual;
        }

        $this->manual = new MappingManualDAO(FullContactIntegration::NAME);

        $fields           = $this->fieldRepository->getFields($this->object_name);
        $objectMappingDAO = new ObjectMappingDAO(Contact::NAME, $this->object_name);

        foreach ($fields as $fieldAlias => $mauticFieldAlias) {
            if (!isset($fields[$fieldAlias])) {
                continue;
            }

            /** @var Field $field */
            $field = $fields[$fieldAlias];

            // Configure how fields should be handled by the sync engine as determined by the user's configuration.
            $objectMappingDAO->addFieldMapping(
                $mauticFieldAlias,
                $fieldAlias,
                ObjectMappingDAO::SYNC_TO_MAUTIC,
                false
            );

            $this->manual->addObjectMapping($objectMappingDAO);
        }

        return $this->manual;
    }
}
