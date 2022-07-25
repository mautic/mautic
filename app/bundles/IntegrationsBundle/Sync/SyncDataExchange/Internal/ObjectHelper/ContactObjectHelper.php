<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper;

use Doctrine\DBAL\Connection;
use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\ReferenceValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;

class ContactObjectHelper implements ObjectHelperInterface
{
    /**
     * @var LeadModel
     */
    private $model;

    /**
     * @var LeadRepository
     */
    private $repository;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var array
     */
    private $availableFields;

    /**
     * @var DoNotContactModel
     */
    private $dncModel;

    public function __construct(LeadModel $model, LeadRepository $repository, Connection $connection, FieldModel $fieldModel, DoNotContactModel $dncModel)
    {
        $this->model      = $model;
        $this->repository = $repository;
        $this->connection = $connection;
        $this->fieldModel = $fieldModel;
        $this->dncModel   = $dncModel;
    }

    /**
     * @param ObjectChangeDAO[] $objects
     *
     * @return ObjectMapping[]
     */
    public function create(array $objects): array
    {
        $availableFields = $this->getAvailableFields();
        $objectMappings  = [];

        foreach ($objects as $object) {
            $contact = new Lead();
            $fields  = $object->getFields();

            $pseudoFields = [];
            foreach ($fields as $field) {
                if (in_array($field->getName(), $availableFields)) {
                    $this->addUpdatedFieldToContact($contact, $field);
                } else {
                    $pseudoFields[$field->getName()] = $field;
                }
            }

            // Create the contact before processing pseudo fields
            $this->model->saveEntity($contact);

            // Process the pseudo field
            $this->processPseudoFields($contact, $pseudoFields, $object->getIntegration());

            // Detach to free RAM
            $this->repository->detachEntity($contact);

            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                sprintf(
                    'Created lead ID %d',
                    $contact->getId()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            $objectMapping = new ObjectMapping();
            $objectMapping->setLastSyncDate($object->getChangeDateTime())
                ->setIntegration($object->getIntegration())
                ->setIntegrationObjectName($object->getMappedObject())
                ->setIntegrationObjectId($object->getMappedObjectId())
                ->setInternalObjectName(Contact::NAME)
                ->setInternalObjectId($contact->getId());
            $objectMappings[] = $objectMapping;
        }

        return $objectMappings;
    }

    /**
     * @param ObjectChangeDAO[] $objects
     *
     * @return UpdatedObjectMappingDAO[]
     */
    public function update(array $ids, array $objects): array
    {
        /** @var Lead[] $contacts */
        $contacts = $this->model->getEntities(['ids' => $ids]);
        DebugLogger::log(
            MauticSyncDataExchange::NAME,
            sprintf(
                'Found %d leads to update with ids %s',
                count($contacts),
                implode(', ', $ids)
            ),
            __CLASS__.':'.__FUNCTION__
        );

        $availableFields      = $this->getAvailableFields();
        $updatedMappedObjects = [];

        foreach ($contacts as $contact) {
            /** @var ObjectChangeDAO $changedObject */
            $changedObject = $objects[$contact->getId()];

            $fields = $changedObject->getFields();

            $pseudoFields = [];
            foreach ($fields as $field) {
                if (in_array($field->getName(), $availableFields)) {
                    $this->addUpdatedFieldToContact($contact, $field);
                } else {
                    $pseudoFields[$field->getName()] = $field;
                }
            }

            // Create the contact before processing pseudo fields
            $this->model->saveEntity($contact);

            // Process the pseudo field
            $this->processPseudoFields($contact, $pseudoFields, $changedObject->getIntegration());

            $this->repository->detachEntity($contact);

            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                sprintf(
                    'Updated lead ID %d',
                    $contact->getId()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            // Integration name and ID are stored in the change's mappedObject/mappedObjectId
            $updatedMappedObjects[] = new UpdatedObjectMappingDAO(
                $changedObject->getIntegration(),
                $changedObject->getMappedObject(),
                $changedObject->getMappedObjectId(),
                $changedObject->getChangeDateTime()
            );
        }

        return $updatedMappedObjects;
    }

    private function addUpdatedFieldToContact(Lead $contact, FieldDAO $field): void
    {
        $value = $field->getValue()->getNormalizedValue();

        if ($value instanceof ReferenceValueDAO) {
            $value = $this->getReferenceValueForField($value);
        }

        $contact->addUpdatedField($field->getName(), $value);
    }

    private function getReferenceValueForField(ReferenceValueDAO $value): ?string
    {
        if (MauticSyncDataExchange::OBJECT_COMPANY === $value->getType() && 0 < $value->getValue()) {
            try {
                return $this->getCompanyNameById($value->getValue());
            } catch (ObjectNotFoundException $e) {
            }
        }

        return null;
    }

    /**
     * Unfortunately the LeadRepository doesn't give us what we need so we have to write our own queries.
     *
     * @param int $start
     * @param int $limit
     */
    public function findObjectsBetweenDates(\DateTimeInterface $from, \DateTimeInterface $to, $start, $limit): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('l.date_identified'),
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->isNotNull('l.date_modified'),
                            $qb->expr()->gte('l.date_modified', ':dateFrom'),
                            $qb->expr()->lt('l.date_modified', ':dateTo')
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->isNull('l.date_modified'),
                            $qb->expr()->gte('l.date_added', ':dateFrom'),
                            $qb->expr()->lt('l.date_added', ':dateTo')
                        )
                    )
                )
            )
            ->setParameter('dateFrom', $from->format('Y-m-d H:i:s'))
            ->setParameter('dateTo', $to->format('Y-m-d H:i:s'))
            ->setFirstResult($start)
            ->setMaxResults($limit);

        return $qb->execute()->fetchAll();
    }

    public function findObjectsByIds(array $ids): array
    {
        if (!count($ids)) {
            return [];
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where(
                $qb->expr()->in('id', $ids)
            );

        return $qb->execute()->fetchAll();
    }

    public function findObjectsByFieldValues(array $fields): array
    {
        $q = $this->connection->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        foreach ($fields as $col => $val) {
            // Use andWhere because Mautic treats conflicting unique identifiers as different objects
            $q->{$this->repository->getUniqueIdentifiersWherePart()}("l.$col = :".$col)
                ->setParameter($col, $val);
        }

        return $q->execute()->fetchAll();
    }

    public function getDoNotContactStatus(int $contactId, string $channel): int
    {
        $q = $this->connection->createQueryBuilder();

        $q->select('dnc.reason')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('dnc.lead_id', ':contactId'),
                    $q->expr()->eq('dnc.channel', ':channel')
                )
            )
            ->setParameter('contactId', $contactId)
            ->setParameter('channel', $channel)
            ->setMaxResults(1);

        $status = $q->execute()->fetchColumn();

        if (false === $status) {
            return DoNotContact::IS_CONTACTABLE;
        }

        return (int) $status;
    }

    public function findOwnerIds(array $objectIds): array
    {
        if (empty($objectIds)) {
            return [];
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->select('c.owner_id, c.id');
        $qb->from(MAUTIC_TABLE_PREFIX.'leads', 'c');
        $qb->where('c.owner_id IS NOT NULL');
        $qb->andWhere('c.id IN (:objectIds)');
        $qb->setParameter('objectIds', $objectIds, Connection::PARAM_INT_ARRAY);

        return $qb->execute()->fetchAll();
    }

    /**
     * @throws ObjectNotFoundException
     */
    private function getCompanyNameById(int $id): string
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('c.companyname');
        $qb->from(MAUTIC_TABLE_PREFIX.'companies', 'c');
        $qb->where('c.id = :id');
        $qb->setParameter('id', $id);

        $name = $qb->execute()->fetchColumn();

        if (false === $name) {
            throw new ObjectNotFoundException("Company with ID {$id} was not found.");
        }

        return $name;
    }

    private function getAvailableFields(): array
    {
        if (null === $this->availableFields) {
            $availableFields = $this->fieldModel->getFieldList(false, false);

            $this->availableFields = array_keys($availableFields);
        }

        return $this->availableFields;
    }

    /**
     * @param FieldDAO[] $fields
     */
    private function processPseudoFields(Lead $contact, array $fields, string $integration): void
    {
        foreach ($fields as $name => $field) {
            if (0 === strpos($name, 'mautic_internal_dnc_')) {
                $channel   = str_replace('mautic_internal_dnc_', '', $name);

                $dncReason = $this->getDoNotContactReason($field->getValue()->getNormalizedValue());

                if (DoNotContact::IS_CONTACTABLE === $dncReason) {
                    $this->dncModel->removeDncForContact($contact->getId(), $channel);

                    continue;
                }
                $this->dncModel->addDncForContact(
                    $contact->getId(),
                    $channel,
                    $dncReason,
                    $integration,
                    true,
                    true,
                    true
                );
            }

            if ('owner_id' == $name) {
                $ownerId = $field->getValue()->getNormalizedValue();
                $this->model->updateLeadOwner($contact, $ownerId);
            }

            // Ignore all others as unrecognized
        }
    }

    /**
     * @param $value
     *
     * @return int
     */
    private function getDoNotContactReason($value)
    {
        $value = (int) $value;

        if (in_array($value, [DoNotContact::BOUNCED, DoNotContact::UNSUBSCRIBED, DoNotContact::MANUAL, DoNotContact::IS_CONTACTABLE])) {
            return $value;
        }

        // Assume manually removed
        return DoNotContact::MANUAL;
    }
}
