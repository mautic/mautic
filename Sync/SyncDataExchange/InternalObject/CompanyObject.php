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


use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use MauticPlugin\IntegrationsBundle\Entity\ObjectMapping;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
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
        }
    }
}