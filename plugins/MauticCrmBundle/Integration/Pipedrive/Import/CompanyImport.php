<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\HttpFoundation\Response;

class CompanyImport extends AbstractImport
{
    /**
     * @var CompanyModel
     */
    private $companyModel;

    /**
     * CompanyImport constructor.
     *
     * @param EntityManager $em
     * @param CompanyModel  $companyModel
     */
    public function __construct(EntityManager $em, CompanyModel $companyModel)
    {
        parent::__construct($em);

        $this->companyModel = $companyModel;
    }

    /**
     * @param array $data
     *
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

        $data       = $this->convertPipedriveData($data, $this->getIntegration()->getApiHelper()->getFields(SELF::ORGANIZATION_ENTITY_TYPE));
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
     * @param array $data
     *
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

        $data    = $this->convertPipedriveData($data, $this->getIntegration()->getApiHelper()->getFields(SELF::ORGANIZATION_ENTITY_TYPE));
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
     * @param array $data
     *
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

        /** @var Company $company */
        $company = $this->em->getRepository(Company::class)->findOneById($integrationEntity->getInternalEntityId());

        if (!$company) {
            throw new \Exception('Company doesn\'t exists', Response::HTTP_NOT_FOUND);
        }

        // prevent listeners from exporting
        $company->setEventData('pipedrive.webhook', 1);
        $this->companyModel->deleteEntity($company);

        if (!empty($company->deletedId)) {
            $this->em->remove($integrationEntity);
        }
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
     * @param         $integrationOwnerId
     * @param Company $company
     */
    private function addOwnerToCompany($integrationOwnerId, Company $company)
    {
        $mauticOwner = $this->getOwnerByIntegrationId($integrationOwnerId);
        $company->setOwner($mauticOwner);
    }
}
