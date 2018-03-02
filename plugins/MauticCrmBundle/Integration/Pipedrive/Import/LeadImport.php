<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Response;

class LeadImport extends AbstractImport
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var CompanyModel
     */
    private $companyModel;

    /**
     * LeadImport constructor.
     *
     * @param EntityManager $em
     * @param LeadModel     $leadModel
     */
    public function __construct(EntityManager $em, LeadModel $leadModel, CompanyModel $companyModel)
    {
        parent::__construct($em);

        $this->leadModel    = $leadModel;
        $this->companyModel = $companyModel;
    }

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function create(array $data = [])
    {
        $integrationEntity = $this->getLeadIntegrationEntity(['integrationEntityId' => $data['id']]);

        if ($integrationEntity) {
            throw new \Exception('Lead already have integration', Response::HTTP_CONFLICT);
        }
        $data         = $this->convertPipedriveData($data);
        $dataToUpdate = $this->getIntegration()->populateMauticLeadData($data);

        if (1 == 0 && !defined('PHPUNIT_TESTSUITE') && !$lead =  $this->leadModel->getExistingLead($dataToUpdate)) {
            $lead = new Lead();
        }
        // prevent listeners from exporting
        $lead->setEventData('pipedrive.webhook', 1);

        $this->leadModel->setFieldValues($lead, $dataToUpdate);

        if (isset($data['owner_id'])) {
            $this->addOwnerToLead($data['owner_id'], $lead);
        }
        $this->leadModel->saveEntity($lead);

        $integrationEntity = $this->createIntegrationLeadEntity(new \DateTime(), $data['id'], $lead->getId());

        $this->em->persist($integrationEntity);
        $this->em->flush();

        if (isset($data['org_id']) && $this->getIntegration()->isCompanySupportEnabled()) {
            $this->addLeadToCompany($data['org_id'], $lead);
            $this->em->flush();
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function update(array $data = [])
    {
        $integrationEntity = $this->getLeadIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            return $this->create($data);
        }

        /** @var Lead $lead * */
        $lead         = $this->em->getRepository(Lead::class)->findOneById($integrationEntity->getInternalEntityId());

        // prevent listeners from exporting
        $lead->setEventData('pipedrive.webhook', 1);

        $data         = $this->convertPipedriveData($data);
        $dataToUpdate = $this->getIntegration()->populateMauticLeadData($data);

        $lastSyncDate      = $integrationEntity->getLastSyncDate();
        $leadDateModified  = $lead->getDateModified();

        if ($lastSyncDate >= $leadDateModified) {
            return false;
        } //Do not push lead if contact was modified in Mautic, and we don't wanna mofify it

        $lead->setDateModified(new \DateTime());
        $this->leadModel->setFieldValues($lead, $dataToUpdate);

        if (!isset($data['owner_id']) && $lead->getOwner()) {
            $lead->setOwner(null);
        } elseif (isset($data['owner_id'])) {
            $this->addOwnerToLead($data['owner_id'], $lead);
        }
        $this->leadModel->saveEntity($lead);

        $integrationEntity->setLastSyncDate(new \DateTime());
        $this->em->persist($integrationEntity);
        $this->em->flush();

        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return;
        }

        if (!isset($data['org_id']) && $lead->getCompany()) {
            $this->removeLeadFromCompany($lead->getCompany(), $lead);
        } elseif (isset($data['org_id'])) {
            $this->addLeadToCompany($data['org_id'], $lead);
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @throws \Exception
     */
    public function delete(array $data = [])
    {
        $integrationEntity = $this->getLeadIntegrationEntity(['integrationEntityId' => $data['id']]);

        if (!$integrationEntity) {
            throw new \Exception('Lead doesn\'t have integration', Response::HTTP_NOT_FOUND);
        }

        /** @var Lead $lead */
        $lead = $this->em->getRepository(Lead::class)->findOneById($integrationEntity->getInternalEntityId());

        if (!$lead) {
            throw new \Exception('Lead doesn\'t exists in Mautic', Response::HTTP_NOT_FOUND);
        }

        // prevent listeners from exporting
        $lead->setEventData('pipedrive.webhook', 1);

        $this->leadModel->deleteEntity($lead);

        if (!empty($lead->deletedId)) {
            $this->em->remove($integrationEntity);
        }
    }

    /**
     * @param      $integrationOwnerId
     * @param Lead $lead
     */
    private function addOwnerToLead($integrationOwnerId, Lead $lead)
    {
        $mauticOwner = $this->getOwnerByIntegrationId($integrationOwnerId);
        $lead->setOwner($mauticOwner);
    }

    /**
     * @param      $companyName
     * @param Lead $lead
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function removeLeadFromCompany($companyName, Lead $lead)
    {
        $company = $this->em->getRepository(Company::class)->findOneByName($companyName);

        if (!$company) {
            return;
        }

        $this->companyModel->removeLeadFromCompany($company, $lead);
    }

    /**
     * @param      $integrationCompanyId
     * @param Lead $lead
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function addLeadToCompany($integrationCompanyId, Lead $lead)
    {
        $integrationEntityCompany = $this->getCompanyIntegrationEntity(['integrationEntityId' => $integrationCompanyId]);

        if (!$integrationEntityCompany) {
            return;
        }

        if (!$company = $this->companyModel->getEntity($integrationEntityCompany->getInternalEntityId())) {
            return;
        }

        $this->companyModel->addLeadToCompany($company, $lead);
    }
}
