<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import;

use Mautic\LeadBundle\Entity\Company;
use Symfony\Component\HttpFoundation\Response;

class CompanyImport extends AbstractImport
{
    public function create(array $data = [])
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return; //feature disabled
        }

        $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $data['id']]);

        if ($integrationEntity) {
            throw new \Exception('Company already have integration', Response::HTTP_CONFLICT);
        }

        $company = new Company();
        $data    = $this->convertPipedriveData($data);
        $this->populateMappedCompanyData($company, $data);

        if ($data['owner_id']) {
            $this->addOwnerToCompany($data['owner_id'], $company);
        }

        $company->setDateAdded(new \DateTime());

        $this->em->persist($company);
        $this->em->flush();

        $integrationEntity = $this->createIntegrationCompanyEntity(new \DateTime(), $data['id'], $company->getId());

        $this->em->persist($integrationEntity);
        $this->em->flush();

        return true;
    }

    public function update(array $data = [])
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return; //feature disabled
        }

        $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            return $this->create($data);
        }

        $company = $this->em->getRepository(Company::class)->findOneById($integrationEntity->getInternalEntityId());
        $data    = $this->convertPipedriveData($data);
        $this->populateMappedCompanyData($company, $data);

        $integrationEntity->setLastSyncDate(new \DateTime());

        if ($data['owner_id']) {
            $this->addOwnerToCompany($data['owner_id'], $company);
        }

        $company->setDateModified(new \DateTime());

        $this->em->persist($company);
        $this->em->persist($integrationEntity);

        $this->em->flush();
    }

    public function delete(array $data = [])
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return; //feature disabled
        }

        $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            throw new \Exception('Company doesn\'t have integration', Response::HTTP_NOT_FOUND);
        }

        $company = $this->em->getRepository(Company::class)->findOneById($integrationEntity->getInternalEntityId());

        if (!$company) {
            throw new \Exception('Company doesn\'t exists', Response::HTTP_NOT_FOUND);
        }

        $this->em->transactional(function ($em) use ($company, $integrationEntity) {
            $em->remove($company);
            $em->remove($integrationEntity);
        });
    }

    private function populateMappedCompanyData(Company $company, $data)
    {
        $mappedData    = [];
        $companyFields = $this->getIntegration()->getIntegrationSettings()->getFeatureSettings()['companyFields'];

        foreach ($companyFields as $externalField => $internalField) {
            if (!array_key_exists($externalField, $data)) {
                continue;
            }

            $fieldName = substr($internalField, strlen($company::FIELD_ALIAS)); //remove column alias

            $mappedData[$fieldName] = $data[$externalField];
        }

        foreach ($mappedData as $field => $value) {
            $company->addUpdatedField($field, $value);
        }
    }

    private function addOwnerToCompany($integrationOwnerId, Company $company)
    {
        $mauticOwner = $this->getOwnerByIntegrationId($integrationOwnerId);
        $company->setOwner($mauticOwner);
    }
}
