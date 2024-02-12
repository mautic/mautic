<?php

namespace Mautic\PluginBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Integration\IntegrationObject;

/**
 * @extends FormModel<IntegrationEntity>
 */
class IntegrationEntityModel extends FormModel
{
    public function getIntegrationEntityRepository()
    {
        return $this->em->getRepository(IntegrationEntity::class);
    }

    public function logDataSync(IntegrationObject $integrationObject): void
    {
    }

    public function getSyncedRecords(IntegrationObject $integrationObject, $integrationName, $recordList, $internalEntityId = null)
    {
        if (!$formattedRecords = $this->formatListOfContacts($recordList)) {
            return [];
        }

        $integrationEntityRepo = $this->getIntegrationEntityRepository();

        return $integrationEntityRepo->getIntegrationsEntityId(
            $integrationName,
            $integrationObject->getType(),
            $integrationObject->getInternalType(),
            $internalEntityId,
            null,
            null,
            false,
            0,
            0,
            $formattedRecords
        );
    }

    /**
     * @return array<mixed, array<'id', mixed>>
     */
    public function getRecordList($integrationObject): array
    {
        $recordList = [];

        foreach ($integrationObject->getRecords() as $record) {
            $recordList[$record['Id']] = [
                'id' => $record['Id'],
            ];
        }

        return $recordList;
    }

    public function formatListOfContacts($recordList): ?string
    {
        if (empty($recordList)) {
            return null;
        }

        $csList = is_array($recordList) ? implode('", "', array_keys($recordList)) : $recordList;

        return '"'.$csList.'"';
    }

    public function getMauticContactsById($mauticContactIds, $integrationName, $internalObject)
    {
        if (!$formattedRecords = $this->formatListOfContacts($mauticContactIds)) {
            return [];
        }
        $integrationEntityRepo = $this->getIntegrationEntityRepository();

        return $integrationEntityRepo->getIntegrationsEntityId(
            $integrationName,
            null,
            $internalObject,
            null,
            null,
            null,
            false,
            0,
            0,
            $formattedRecords
        );
    }

    /**
     * @param int $id
     *
     * @return IntegrationEntity|null
     */
    public function getEntityByIdAndSetSyncDate($id, \DateTime $dateTime)
    {
        $entity = $this->getIntegrationEntityRepository()->find($id);
        if ($entity) {
            $entity->setLastSyncDate($dateTime);
        }

        return $entity;
    }
}
