<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper;

use Doctrine\DBAL\Connection;
use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;

class ContactObjectHelper implements ObjectHelperInterface
{
    private ?array $availableFields = null;

    public function __construct(
        private LeadModel $model,
        private LeadRepository $repository,
        private Connection $connection,
        private FieldModel $fieldModel,
        private DoNotContactModel $dncModel
    ) {
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
                    $contact->addUpdatedField($field->getName(), $field->getValue()->getNormalizedValue());
                } else {
                    $pseudoFields[$field->getName()] = $field;
                }
            }

            $contact->setManipulator(new LeadManipulator('integrations', 'create'));

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
                self::class.':'.__FUNCTION__
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
            self::class.':'.__FUNCTION__
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
                    $contact->addUpdatedField($field->getName(), $field->getValue()->getNormalizedValue());
                } else {
                    $pseudoFields[$field->getName()] = $field;
                }
            }

            $contact->setManipulator(new LeadManipulator('integrations', 'update'));

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
                self::class.':'.__FUNCTION__
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
                $qb->expr()->and(
                    $qb->expr()->isNotNull('l.date_identified'),
                    $qb->expr()->or(
                        $qb->expr()->and(
                            $qb->expr()->isNotNull('l.date_modified'),
                            $qb->expr()->gte('l.date_modified', ':dateFrom'),
                            $qb->expr()->lt('l.date_modified', ':dateTo')
                        ),
                        $qb->expr()->and(
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

        return $qb->executeQuery()->fetchAllAssociative();
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

        return $qb->executeQuery()->fetchAllAssociative();
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

        return $q->executeQuery()->fetchAllAssociative();
    }

    public function getDoNotContactStatus(int $contactId, string $channel): int
    {
        $q = $this->connection->createQueryBuilder();

        $q->select('dnc.reason')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('dnc.lead_id', ':contactId'),
                    $q->expr()->eq('dnc.channel', ':channel')
                )
            )
            ->setParameter('contactId', $contactId)
            ->setParameter('channel', $channel)
            ->setMaxResults(1);

        $status = $q->executeQuery()->fetchOne();

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

        return $qb->executeQuery()->fetchAllAssociative();
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
            if (str_starts_with($name, 'mautic_internal_dnc_')) {
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

    private function getDoNotContactReason($value): int
    {
        $value = (int) $value;

        if (in_array($value, [DoNotContact::BOUNCED, DoNotContact::UNSUBSCRIBED, DoNotContact::MANUAL, DoNotContact::IS_CONTACTABLE])) {
            return $value;
        }

        // Assume manually removed
        return DoNotContact::MANUAL;
    }

    public function findObjectById(int $id): ?Lead
    {
        return $this->repository->getEntity($id);
    }

    /**
     * @throws ImportFailedException
     */
    public function setFieldValues(Lead $lead): void
    {
        $this->model->setFieldValues($lead, []);
    }
}
