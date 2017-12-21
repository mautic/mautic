<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;

class LeadImport extends AbstractImport
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function create(array $data = [])
    {
        $integrationEntity = $this->getLeadIntegrationEntity(['integrationEntityId' => $data['id']]);

        if ($integrationEntity) {
            throw new \Exception('Lead already have integration', Response::HTTP_CONFLICT);
        }

        $data         = $this->convertPipedriveData($data);
        $dataToUpdate = $this->getIntegration()->populateMauticLeadData($data);

        $lead = new Lead();

        foreach ($dataToUpdate as $field => $value) {
            $lead->addUpdatedField($field, $value);
        }

        if (isset($data['owner_id'])) {
            $this->addOwnerToLead($data['owner_id'], $lead);
        }

        $lead->setDateAdded(new \DateTime());
        $lead->setPreferredProfileImage('gravatar');
        $this->em->getRepository(Lead::class)->saveEntity($lead);

        $integrationEntity = $this->createIntegrationLeadEntity(new \DateTime(), $data['id'], $lead->getId());

        $this->em->persist($integrationEntity);
        $this->em->flush();

        if (isset($data['org_id']) && $this->getIntegration()->isCompanySupportEnabled()) {
            $this->addLeadToCompany($data['org_id'], $lead);
            $this->em->flush();
        }

        return true;
    }

    public function update(array $data = [])
    {
        $integrationEntity = $this->getLeadIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            return $this->create($data);
        }

        /** @var $lead Lead **/
        $lead         = $this->em->getRepository(Lead::class)->findOneById($integrationEntity->getInternalEntityId());
        $data         = $this->convertPipedriveData($data);
        $dataToUpdate = $this->getIntegration()->populateMauticLeadData($data);

        foreach ($dataToUpdate as $field => $value) {
            $lead->addUpdatedField($field, $value);
        }

        $lead->setDateModified(new \DateTime());

        if (!isset($data['owner_id']) && $lead->getOwner()) {
            $lead->setOwner(null);
        } elseif (isset($data['owner_id'])) {
            $this->addOwnerToLead($data['owner_id'], $lead);
        }

        $integrationEntity->setLastSyncDate(new \DateTime());

        $this->em->getRepository(Lead::class)->saveEntity($lead);
        $this->em->persist($integrationEntity);

        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return;
        }

        if (!isset($data['org_id']) && $lead->getCompany()) {
            $this->removeLeadFromCompany($lead->getCompany(), $lead);
        } elseif (isset($data['org_id'])) {
            $this->addLeadToCompany($data['org_id'], $lead);
        }

        $this->em->flush();
    }

    public function delete(array $data = [])
    {
        $integrationEntity = $this->getLeadIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            throw new \Exception('Lead doesn\'t have integration', Response::HTTP_NOT_FOUND);
        }

        $lead = $this->em->getRepository(Lead::class)->findOneById($integrationEntity->getInternalEntityId());

        if (!$lead) {
            throw new \Exception('Lead doesn\'t exists in Mautic', Response::HTTP_NOT_FOUND);
        }

        $this->em->transactional(function ($em) use ($lead, $integrationEntity) {
            $em->remove($lead);
            $em->remove($integrationEntity);
        });
    }

    private function addOwnerToLead($integrationOwnerId, Lead $lead)
    {
        $mauticOwner = $this->getOwnerByIntegrationId($integrationOwnerId);
        $lead->setOwner($mauticOwner);
    }

    private function removeLeadFromCompany($companyName, Lead $lead)
    {
        $company = $this->em->getRepository(Company::class)->findOneByName($companyName);

        if (!$company) {
            return;
        }

        $companyLead = $this->em->getRepository(CompanyLead::class)->findOneBy([
            'lead'    => $lead,
            'company' => $company->getId(),
        ]);

        if (!$companyLead) {
            return;
        }

        $this->em->remove($companyLead);
    }

    private function addLeadToCompany($integrationCompanyId, Lead $lead)
    {
        $integrationEntityCompany = $this->getCompanyIntegrationEntity(['integrationEntityId' => $integrationCompanyId]);

        if (!$integrationEntityCompany) {
            return;
        }

        $company = $this->em->getRepository(Company::class)->findOneById($integrationEntityCompany->getInternalEntityId());

        $companyLead = $this->em->getRepository(CompanyLead::class)->findOneBy([
            'lead'    => $lead,
            'company' => $company->getId(),
        ]);

        if ($companyLead) {
            return;
        }

        $companyLead = new CompanyLead();
        $companyLead->setCompany($company);
        $companyLead->setLead($lead);
        $companyLead->setManuallyAdded(false);
        $companyLead->setDateAdded(new \DateTime());

        $this->em->persist($companyLead);
        $lead->setCompany($company->getName());
    }
}
