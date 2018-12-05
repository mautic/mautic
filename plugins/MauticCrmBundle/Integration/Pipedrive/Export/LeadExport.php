<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\AbstractPipedrive;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LeadExport extends AbstractPipedrive
{
    /**
     * @var CompanyExport
     */
    private $companyExport;

    /**
     * LeadExport constructor.
     *
     * @param EntityManager $em
     * @param CompanyExport $companyExport
     */
    public function __construct(EntityManager $em, CompanyExport $companyExport)
    {
        $this->em            = $em;
        $this->companyExport = $companyExport;
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function create(Lead $lead)
    {
        // stop for anynomouse
        if ($lead->isAnonymous() || empty($lead->getEmail())) {
            return false;
        }

        $mappedData        = $this->getMappedLeadData($lead);
        $leadId            = $lead->getId();

        /** @var IntegrationEntity $integrationEntity */
        $integrationEntity = $this->getLeadIntegrationEntity(['internalEntityId' => $leadId]);
        $personData        = $this->getIntegration()->getApiHelper()->findByEmail($lead->getEmail());
        // Pipedrive contact already exists, then create just integration entity
        if (!$integrationEntity && !empty($personData)) {
            $integrationEntityCreate = $this->createIntegrationLeadEntity(new \DateTime(), $personData[0]['id'], $leadId);
            $integrationEntity       = clone $integrationEntityCreate;
            $this->em->persist($integrationEntityCreate);
            $this->em->flush();
        }

        // Integration entity exist and Pipedrive contact exist, then just update Pipedrive contact
        if ($integrationEntity && !empty($personData)) {
            return $this->update($lead);
        }

        try {
            $createdLeadData   = $this->getIntegration()->getApiHelper()->createLead($mappedData, $lead);
            if (empty($createdLeadData['id'])) {
                return false;
            }
            $integrationEntity = $this->createIntegrationLeadEntity(new \DateTime(), $createdLeadData['id'], $leadId);

            $this->em->persist($integrationEntity);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->getIntegration()->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function update(Lead $lead)
    {
        $leadId            = $lead->getId();
        $integrationEntity = $this->getLeadIntegrationEntity(['internalEntityId' => $leadId]);
        if (!$integrationEntity) {
            // create new contact

            return $this->create($lead);
        }

        try {
            $mappedData = $this->getMappedLeadData($lead);
            $this->getIntegration()->getApiHelper()->updateLead($mappedData, $integrationEntity->getIntegrationEntityId());

            $integrationEntity->setLastSyncDate(new \DateTime());

            $this->em->persist($integrationEntity);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->getIntegration()->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function delete(Lead $lead)
    {
        $integrationEntity = $this->getLeadIntegrationEntity(['internalEntityId' => $lead->getId()]);

        if (!$integrationEntity) {
            return true;
        }

        try {
            $this->getIntegration()->getApiHelper()->deleteLead($integrationEntity->getIntegrationEntityId());

            $this->em->remove($integrationEntity);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->getIntegration()->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @param Lead $lead
     *
     * @return mixed
     */
    private function getMappedLeadData(Lead $lead)
    {
        $mappedData = [];
        $leadFields = $this->getIntegration()->getIntegrationSettings()->getFeatureSettings()['leadFields'];

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($leadFields as $externalField => $internalField) {
            if (in_array($externalField, self::NO_ALLOWED_FIELDS_TO_EXPORT)) {
                continue;
            }
            $mappedData[$externalField] = $accessor->getValue($lead, $internalField);
        }

        if ($this->getIntegration()->isCompanySupportEnabled()) {
            $mappedData['org_id'] = $this->getLeadIntegrationCompanyId($lead);
        }

        $mappedData['owner_id'] = $this->getLeadIntegrationOwnerId($lead);

        return $this->convertMauticData($mappedData);
    }

    private function getLeadIntegrationCompanyId(Lead $lead)
    {
        $leadCompanies = $this->em->getRepository(CompanyLead::class)->findBy([
            'lead'            => $lead,
            'manuallyRemoved' => false,
        ]);

        if (!$leadCompanies) {
            return 0;
        }

        $leadCompany = array_pop($leadCompanies);

        $integrationEntityCompany = $this->getCompanyIntegrationEntity(['internalEntityId' => $leadCompany->getCompany()->getId()]);

        if (!$integrationEntityCompany) {
            // check if company already exist on Pipedrive
            $companyData = $this->getIntegration()->getApiHelper()->findCompanyByName($leadCompany->getCompany()->getName(), 0, 1);
            if (!empty($companyData)) {
                $integrationEntityCompany = $this->createIntegrationLeadEntity(new \DateTime(), $companyData[0]['id'], $leadCompany->getCompany()->getId());
                $this->em->persist($integrationEntityCompany);
                $this->em->flush();
            } else {
                // create new company on Pipedrive
                $this->companyExport->setIntegration($this->getIntegration());
                if ($this->companyExport->pushCompany($leadCompany->getCompany())) {
                    $integrationEntityCompany = $this->getCompanyIntegrationEntity(['internalEntityId' => $leadCompany->getCompany()->getId()]);
                }
            }
        }

        if (!$integrationEntityCompany) {
            return 0;
        }

        return $integrationEntityCompany->getIntegrationEntityId();
    }

    private function getLeadIntegrationOwnerId(Lead $lead)
    {
        $mauticOwner = $lead->getOwner();

        if (!$mauticOwner) {
            return 0;
        }

        $pipedriveOwner = $this->em->getRepository(PipedriveOwner::class)->findOneByEmail($mauticOwner->getEmail());

        if (!$pipedriveOwner) {
            return 0;
        }

        return $pipedriveOwner->getOwnerId();
    }

    private function convertMauticData($data)
    {
        $data['name'] = $data['first_name'].' '.$data['last_name'];

        return $data;
    }
}
