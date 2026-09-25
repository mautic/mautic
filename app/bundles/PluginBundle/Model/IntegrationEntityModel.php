<?php

namespace Mautic\PluginBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Integration\IntegrationObject;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<IntegrationEntity>
 */
final class IntegrationEntityModel extends FormModel
{
    public static function getName(): string
    {
        return 'plugin.integration_entity';
    }

    private IntegrationEntityRepository $integrationEntityRepository;

    #[Required]
    public function autowireIntegrationEntityModel(
        IntegrationEntityRepository $integrationEntityRepository,
    ): void {
        $this->integrationEntityRepository = $integrationEntityRepository;
    }

    public function getSyncedRecords(IntegrationObject $integrationObject, $integrationName, $recordList, $internalEntityId = null): array
    {
        if (!$formattedRecords = $this->formatListOfContacts($recordList)) {
            return [];
        }

        return $this->integrationEntityRepository->getIntegrationsEntityId(
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

    public function formatListOfContacts(array|string $recordList): ?string
    {
        if (empty($recordList)) {
            return null;
        }

        $csList = is_array($recordList) ? implode('", "', array_keys($recordList)) : $recordList;

        return '"'.$csList.'"';
    }

    public function getMauticContactsById($mauticContactIds, $integrationName, $internalObject): array
    {
        if (!$formattedRecords = $this->formatListOfContacts($mauticContactIds)) {
            return [];
        }

        return $this->integrationEntityRepository->getIntegrationsEntityId(
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
     * @return IntegrationEntity|null
     */
    public function getEntityByIdAndSetSyncDate(int $id, \DateTime $dateTime): ?object
    {
        $entity = $this->integrationEntityRepository->find($id);
        if ($entity) {
            $entity->setLastSyncDate($dateTime);
        }

        return $entity;
    }
}
