<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\InternalObject;


use Doctrine\DBAL\Connection;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\IntegrationsBundle\Entity\ObjectMapping;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class ContactObject implements ObjectInterface
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
     * ContactObject constructor.
     *
     * @param LeadModel      $model
     * @param LeadRepository $repository
     * @param Connection     $connection
     */
    public function __construct(LeadModel $model, LeadRepository $repository, Connection $connection)
    {
        $this->model      = $model;
        $this->repository = $repository;
        $this->connection = $connection;
    }

    /**
     * @param ObjectChangeDAO[] $objects
     *
     * @return ObjectMapping[]
     */
    public function create(array $objects)
    {
        $objectMappings = [];
        foreach ($objects as $object) {
            $contact = new Lead();
            $fields  = $object->getFields();
            foreach ($fields as $field) {
                $contact->addUpdatedField($field->getName(), $field->getValue()->getNormalizedValue());
            }

            $this->model->saveEntity($contact);
            $this->repository->detachEntity($contact);

            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                sprintf(
                    "Created lead ID %d",
                    $contact->getId()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            $objectMapping = new ObjectMapping();
            $objectMapping->setLastSyncDate($contact->getDateAdded())
                ->setIntegration($object->getIntegration())
                ->setIntegrationObjectName($object->getMappedObject())
                ->setIntegrationObjectId($object->getMappedObjectId())
                ->setInternalObjectName(MauticSyncDataExchange::OBJECT_CONTACT)
                ->setInternalObjectId($contact->getId());
            $objectMappings[] = $objectMapping;
        }

        return $objectMappings;
    }

    /**
     * @param array             $ids
     * @param ObjectChangeDAO[] $objects
     *
     * @return UpdatedObjectMappingDAO[]
     */
    public function update(array $ids, array $objects)
    {
        /** @var Lead[] $contacts */
        $contacts = $this->model->getEntities(['ids' => $ids]);
        DebugLogger::log(
            MauticSyncDataExchange::NAME,
            sprintf(
                "Found %d leads to update with ids %s",
                count($contacts),
                implode(", ", $ids)
            ),
            __CLASS__.':'.__FUNCTION__
        );

        $updatedMappedObjects = [];
        foreach ($contacts as $contact) {
            /** @var ObjectChangeDAO $changedObject */
            $changedObject = $objects[$contact->getId()];

            $fields = $changedObject->getFields();

            foreach ($fields as $field) {
                $contact->addUpdatedField($field->getName(), $field->getValue()->getNormalizedValue());
            }

            $this->model->saveEntity($contact);
            $this->repository->detachEntity($contact);

            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                sprintf(
                    "Updated lead ID %d",
                    $contact->getId()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            // Integration name and ID are stored in the change's mappedObject/mappedObjectId
            $updatedMappedObjects[] = new UpdatedObjectMappingDAO(
                $changedObject,
                $changedObject->getObject(),
                $changedObject->getObjectId(),
                $contact->getDateModified()
            );
        }

        return $updatedMappedObjects;
    }

    /**
     * Unfortunately the LeadRepository doesn't give us what we need so we have to write our own queries
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int       $start
     * @param int       $limit
     *
     * @return array
     */
    public function findObjectsBetweenDates(\DateTimeInterface $from, \DateTimeInterface $to, $start, $limit)
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
}