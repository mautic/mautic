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
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use MauticPlugin\IntegrationsBundle\Entity\ObjectMapping;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class CompanyObject implements ObjectInterface
{
    /**
     * @var CompanyModel
     */
    private $model;

    /**
     * @var CompanyRepository
     */
    private $repository;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param ObjectChangeDAO[] $objects
     *
     * @return ObjectMapping[]
     */
    public function create(array $objects)
    {
        $objectMappings = [];
        foreach ($objects as $object) {
            $company = new Company();
            $fields  = $object->getFields();
            foreach ($fields as $field) {
                $company->addUpdatedField($field->getName(), $field->getValue()->getNormalizedValue());
            }

            $this->model->saveEntity($company);
            $this->repository->detachEntity($company);

            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                sprintf(
                    "Created company ID %d",
                    $company->getId()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            $objectMapping = new ObjectMapping();
            $objectMapping->setLastSyncDate($company->getDateAdded())
                ->setIntegration($object->getIntegration())
                ->setIntegrationObjectName($object->getMappedObject())
                ->setIntegrationObjectId($object->getMappedObjectId())
                ->setInternalObjectName(MauticSyncDataExchange::OBJECT_COMPANY)
                ->setInternalObjectId($company->getId());
            $objectMappings[] = $objectMapping;
        }

        return $objectMappings;
    }

    /**
     * @param array             $ids
     * @param ObjectChangeDAO[] $objects
     */
    public function update(array $ids, array $objects)
    {
        /** @var Company[] $companies */
        $companies = $this->model->getEntities(['ids' => $ids]);
        DebugLogger::log(
            MauticSyncDataExchange::NAME,
            sprintf(
                "Found %d companies to update with ids %s",
                count($companies),
                implode(", ", $ids)
            ),
            __CLASS__.':'.__FUNCTION__
        );

        foreach ($companies as $company) {
            $changedObjects = $objects[$company->getId()];

            /** @var ObjectChangeDAO $changedObject */
            foreach ($changedObjects as $changedObject) {
                $fields = $changedObject->getFields();

                foreach ($fields as $field) {
                    $company->addUpdatedField($field->getName(), $field->getValue()->getNormalizedValue());
                }
            }

            $this->model->saveEntity($company);
            $this->repository->detachEntity($company);

            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                sprintf(
                    "Updated company ID %d",
                    $company->getId()
                ),
                __CLASS__.':'.__FUNCTION__
            );
        }
    }

    /**
     * Unfortunately the CompanyRepository doesn't give us what we need so we have to write our own queries
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int                $start
     * @param int                $limit
     *
     * @return array
     */
    public function findObjectsBetweenDates(\DateTimeInterface $from, \DateTimeInterface $to, $start, $limit)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'c')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('c.date_modified'),
                        $qb->expr()->comparison('c.date_modified', 'BETWEEN', ':dateFrom and :dateTo')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('c.date_modified'),
                        $qb->expr()->comparison('c.date_added', 'BETWEEN', ':dateFrom and :dateTo')
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