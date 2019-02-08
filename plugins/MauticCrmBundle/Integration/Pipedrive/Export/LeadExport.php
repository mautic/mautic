<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\AbstractPipedrive;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LeadExport extends AbstractPipedrive
{
    /** @var \DateTime */
    private $startDate;

    /** @var \DateTime */
    private $endDate;
    /**
     * @var CompanyExport
     */
    private $companyExport;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /** @var DateTimeHelper */
    private $dateTimeHelper;

    /**
     * LeadExport constructor.
     *
     * @param EntityManager $em
     * @param CompanyExport $companyExport
     * @param LeadModel     $leadModel
     */
    public function __construct(EntityManager $em, CompanyExport $companyExport, LeadModel $leadModel)
    {
        $this->em            = $em;
        $this->companyExport = $companyExport;
        $this->leadModel     = $leadModel;

        $this->endDate        = new \DateTime('now');
        $this->dateTimeHelper = new DateTimeHelper();
    }

    /**
     * @param string|array $eventLabel
     *
     * @return string
     */
    private function generateDescription($eventLabel)
    {
        if (is_array($eventLabel) && isset($eventLabel['href']) && isset($eventLabel['label'])) {
            return '<a  href="'.$eventLabel['href'].'" '.(!empty($eventLabel['isExternal]']) ? 'target="_blank"' : '').'>'.$eventLabel['label'].'</a>';
        }

        return '';
    }

    /**
     * @param string|array $eventLabel
     *
     * @return string
     */
    private function getEventLabel($eventLabel)
    {
        if (is_array($eventLabel) && isset($eventLabel['label'])) {
            return $eventLabel['label'];
        }

        return $eventLabel;
    }

    public function createActivities(Lead $contact, IntegrationEntity $integrationEntity)
    {
        $config         = $this->getIntegration()->mergeConfigToFeatureSettings();
        $activityEvents = isset($config['activityEvents']) ? $config['activityEvents'] : [];

        // no activity events sync
        if (empty($activityEvents)) {
            return;
        }

        $filters = [
            'search'        => '',
            'includeEvents' => $activityEvents,
            'excludeEvents' => [],
        ];

        if ($this->startDate && $this->endDate) {
            $filters['dateFrom'] = $this->startDate;
            $filters['dateTo']   = $this->endDate;
        }

        $page     = 1;
        while (true) {
            $engagements = $this->leadModel->getEngagements($contact, $filters, null, $page, 100, false);
            $events      = $engagements[0]['events'];
            if (empty($events)) {
                break;
            }
            foreach ($events as $event) {
                // Create activity before exists
                $activityType = $event['event'];
                if (!$this->getIntegration()->getApiHelper()->getActivityType($activityType)) {
                    $this->getIntegration()->getApiHelper()->createActivityType($activityType);
                }
                $this->dateTimeHelper->setDateTime($event['timestamp']);
                $data              = [];
                $data['subject']   = $this->getEventLabel($event['eventLabel']);
                $data['done']      = 1;
                $data['type']      = $activityType;
                $data['person_id'] = $integrationEntity->getIntegrationEntityId();
                $data['due_date']  = $this->dateTimeHelper->getDateTime()->format('Y-m-d');
                $data['due_time']  = $this->dateTimeHelper->getDateTime()->format('H:i:s');
                $data['note']      = $this->generateDescription($event['eventLabel']);
                $this->getIntegration()->getApiHelper()->addActivity($data);
            }
            ++$page;
        }
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
            // we try import all contacts activities
            $this->endDate = null;

            return $this->update($lead);
        }

        try {
            $createdLeadData   = $this->getIntegration()->getApiHelper()->createLead($mappedData, $lead);
            if (empty($createdLeadData['id'])) {
                return false;
            }
            $integrationEntity = $this->createIntegrationLeadEntity(new \DateTime(), $createdLeadData['id'], $leadId);
            $this->createActivities($lead, $integrationEntity);

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

        $this->startDate = $integrationEntity->getLastSyncDate();

        try {
            $mappedData = $this->getMappedLeadData($lead);
            $this->getIntegration()->getApiHelper()->updateLead($mappedData, $integrationEntity->getIntegrationEntityId());
            $this->createActivities($lead, $integrationEntity);
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
