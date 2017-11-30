<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export;

use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\AbstractPipedrive;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LeadExport extends AbstractPipedrive
{
    public function create(Lead $lead)
    {
        $mappedData        = $this->getMappedLeadData($lead);
        $leadId            = $lead->getId();
        $integrationEntity = $this->getLeadIntegrationEntity(['internalEntityId' => $leadId]);

        if ($integrationEntity) {
            return $this->update($lead);
        } // user has integration with Pipedrive

        try {
            $createdLeadData   = $this->getIntegration()->getApiHelper()->createLead($mappedData, $lead);
            $integrationEntity = $this->createIntegrationLeadEntity(new \DateTime(), $createdLeadData['id'], $leadId);

            $this->em->persist($integrationEntity);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->getIntegration()->logIntegrationError($e);
        }

        return false;
    }

    public function update(Lead $lead)
    {
        $leadId            = $lead->getId();
        $integrationEntity = $this->getLeadIntegrationEntity(['internalEntityId' => $leadId]);

        // find contact and create new entity
        if (!$integrationEntity && !empty($lead->getEmail())) {
            $personData = $this->getIntegration()->getApiHelper()->findByEmail($lead->getEmail());
            if (!empty($personData)) {
                $integrationEntity = $this->createIntegrationLeadEntity(new \DateTime(), $personData['id'], $leadId);
                $this->em->persist($integrationEntity);
                $this->em->flush();
            }
        }

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

    private function getMappedLeadData(Lead $lead)
    {
        $mappedData = [];
        $leadFields = $this->getIntegration()->getIntegrationSettings()->getFeatureSettings()['leadFields'];

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($leadFields as $externalField => $internalField) {
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
