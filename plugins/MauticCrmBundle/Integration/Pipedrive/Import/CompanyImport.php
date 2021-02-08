<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveDeletion;
use Symfony\Component\HttpFoundation\Response;

class CompanyImport extends AbstractImport
{
    /**
     * @var CompanyModel
     */
    private $companyModel;

    /**
     * CompanyImport constructor.
     */
    public function __construct(EntityManager $em, CompanyModel $companyModel)
    {
        parent::__construct($em);

        $this->companyModel = $companyModel;
    }

    /**
     * @return bool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function create(array $data = [])
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return false; //feature disabled
        }

        $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $data['id']]);

        if ($integrationEntity) {
            throw new \Exception('Company already have integration', Response::HTTP_CONFLICT);
        }

        $company = new Company();

        // prevent listeners from exporting
        $company->setEventData('pipedrive.webhook', 1);

        $data       = $this->convertPipedriveData($data, $this->getIntegration()->getApiHelper()->getFields(self::ORGANIZATION_ENTITY_TYPE));
        $mappedData = $this->getMappedCompanyData($data);

        // find company exists
        $findCompany = IdentifyCompanyHelper::findCompany($mappedData, $this->companyModel);
        if (isset($findCompany[0]['id'])) {
            throw new \Exception('Company already exist', Response::HTTP_CONFLICT);
        }

        $this->companyModel->setFieldValues($company, $mappedData);
        $this->companyModel->saveEntity($company);

        if ($data['owner_id']) {
            $this->addOwnerToCompany($data['owner_id'], $company);
        }

        $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            $integrationEntity = $this->createIntegrationCompanyEntity(new \DateTime(), $data['id'], $company->getId());
        }
        $this->em->persist($integrationEntity);
        $this->em->flush();

        return true;
    }

    /**
     * @return bool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function update(array $data = [])
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return false; //feature disabled
        }

        $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            return $this->create($data);
        }

        /** @var Company $company */
        $company = $this->companyModel->getEntity($integrationEntity->getInternalEntityId());

        // prevent listeners from exporting
        $company->setEventData('pipedrive.webhook', 1);

        $data    = $this->convertPipedriveData($data, $this->getIntegration()->getApiHelper()->getFields(self::ORGANIZATION_ENTITY_TYPE));
        if ($data['owner_id']) {
            $this->addOwnerToCompany($data['owner_id'], $company);
        }

        $mappedData = $this->getMappedCompanyData($data);

        $this->companyModel->setFieldValues($company, $mappedData, true);
        $this->companyModel->saveEntity($company);

        $integrationEntity->setLastSyncDate(new \DateTime());
        $this->em->persist($integrationEntity);
        $this->em->flush();

        return true;
    }

    /**
     * @throws \Exception
     */
    public function delete(array $data = [])
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return; //feature disabled
        }

        $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            throw new \Exception('Company doesn\'t have integration', Response::HTTP_NOT_FOUND);
        }

        $integrationSettings = $this->getIntegration()->getIntegrationSettings();
        $deleteViaCron       = ($integrationSettings->getIsPublished() && !empty($integrationSettings->getFeatureSettings()['cronDelete']));

        if ($deleteViaCron) {
            $deletion = new PipedriveDeletion();
            $deletion
                ->setObjectType('company')
                ->setDeletedDate(new \DateTime())
                ->setIntegrationEntityId($integrationEntity->getId());

            $this->em->persist($deletion);
            $this->em->flush();
        } else {
            /** @var Company $company */
            $company = $this->em->getRepository(Company::class)->findOneById($integrationEntity->getInternalEntityId());

            if (!$company) {
                throw new \Exception('Company doesn\'t exist', Response::HTTP_NOT_FOUND);
            }

            // prevent listeners from exporting
            $company->setEventData('pipedrive.webhook', 1);
            $this->companyModel->deleteEntity($company);

            if (!empty($company->deletedId)) {
                $this->em->remove($integrationEntity);
            }
        }
    }

    /**
     * @return bool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function merge(array $data = [], $otherId = null)
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return false; //feature disabled
        }

        $otherIntegrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $otherId]);

        if (!$otherIntegrationEntity) {
            // Only destination entity exists, so handle it as an update.
            return $this->update($data);
        }

        $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            // Destination entity doesn't yet exist, so create it first.
            $this->create($data);
            $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $data['id']]);
        }

        /** @var Company $company */
        $company = $this->companyModel->getEntity($integrationEntity->getInternalEntityId());
        /** @var Company $otherCompany */
        $otherCompany = $this->companyModel->getEntity($otherIntegrationEntity->getInternalEntityId());

        // prevent listeners from exporting
        $company->setEventData('pipedrive.webhook', 1);

        $this->companyModel->companyMerge($company, $otherCompany);
        $this->em->remove($otherIntegrationEntity);

        $integrationEntity->setLastSyncDate(new \DateTime());
        $this->em->persist($integrationEntity);
        $this->em->flush();

        return true;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function getMappedCompanyData(array $data)
    {
        $mappedData    = [];
        $companyFields = $this->getIntegration()->getIntegrationSettings()->getFeatureSettings()['companyFields'];

        foreach ($companyFields as $externalField => $internalField) {
            if (!array_key_exists($externalField, $data)) {
                continue;
            }

            $mappedData[$internalField] = $data[$externalField];
        }

        return $mappedData;
    }

    /**
     * @param $integrationOwnerId
     */
    private function addOwnerToCompany($integrationOwnerId, Company $company)
    {
        $mauticOwner = $this->getOwnerByIntegrationId($integrationOwnerId);
        $company->setOwner($mauticOwner);
    }
}
