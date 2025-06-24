<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Form\Type\CampaignActionAnonymizeUserDataType;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Services\AnonymizeContactCompanyData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionAnonymizeUserDataSubscriber implements EventSubscriberInterface
{
    public const KEY_EVENT_NAME = 'lead.action_anonymizeuserdata';

    public function __construct(
        private LeadModel $leadModel,
        private FieldModel $fieldModel,
        private CompanyModel $companyModel,
        private AnonymizeContactCompanyData $anonymizeContactCompanyData,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                  => ['configureAction', 0],
            LeadEvents::ON_CAMPAIGN_ACTION_ANONYMIZE_USER_DATA => ['anonymizeUserData', 10],
        ];
    }

    public function configureAction(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            self::KEY_EVENT_NAME,
            [
                'label'                  => 'mautic.lead.lead.events.anonymize',
                'description'            => 'mautic.lead.lead.events.anonymize_descr',
                // Kept for BC in case plugins are listening to the shared trigger
                'eventName'              => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION, // @phpstan-ignore-line
                'formType'               => CampaignActionAnonymizeUserDataType::class,
                'batchEventName'         => LeadEvents::ON_CAMPAIGN_ACTION_ANONYMIZE_USER_DATA,
            ]
        );
    }

    public function anonymizeUserData(PendingEvent $event): void
    {
        if (!$event->checkContext(self::KEY_EVENT_NAME)) {
            return;
        }

        $properties       = $event->getEvent()->getProperties();
        $pseudonymize     = isset($properties['pseudonymize']) && (bool) $properties['pseudonymize'];
        $leads            = $this->leadModel->getRepository()->findBy(['id' => $event->getContactIds()]);
        $companies        = $this->getCompaniesByLeads($event->getContactIds());

        $idFields                     = array_merge($properties['fieldsToAnonymize'], $properties['fieldsToDelete']);
        $fields                       = $this->fieldModel->getRepository()->findBy(['id' => $idFields]);
        $deleteFormResultsAndAuditLog = false;

        foreach ($fields as $field) {
            if (in_array($field->getId(), $properties['fieldsToDelete'])) {
                [$leads,$companies]           = $this->setDeleteFields($leads, $companies, $field);
                $deleteFormResultsAndAuditLog = true;
                continue;
            }

            if (in_array($field->getId(), $properties['fieldsToAnonymize'])) {
                [$leads,$companies]           = $this->setHashFields($leads, $companies, $field, $pseudonymize);
                $deleteFormResultsAndAuditLog = true;
            }
        }

        if ($deleteFormResultsAndAuditLog) {
            $this->anonymizeContactCompanyData->updateFormResults($event->getContacts(), $pseudonymize);
        }

        if (!empty($leads)) {
            $this->leadModel->saveEntities($leads);
        }

        if (!empty($companies)) {
            $this->companyModel->saveEntities($companies);
        }

        $event->passAll();

        if ($deleteFormResultsAndAuditLog) {
            $this->anonymizeContactCompanyData->deleteAuditLog($event->getContacts());
        }
    }

    /**
     * @param array<int> $leadIds
     *
     * @return array<Company>
     */
    private function getCompaniesByLeads(array $leadIds): array
    {
        $companiesByLead  = $this->companyModel->getRepository()->getCompaniesForContacts($leadIds);
        $companiesId      = [];
        foreach ($companiesByLead as $companies) {
            foreach ($companies as $company) {
                $companiesId[] = $company['id'];
            }
        }

        return $this->companyModel->getRepository()->findBy(['id' => $companiesId]);
    }

    /**
     * @param array<Lead>    $leads
     * @param array<Company> $companies
     *
     * @return array<int,array<mixed>>
     */
    private function setDeleteFields(array $leads, array $companies, LeadField $field): array
    {
        return [
            $this->anonymizeContactCompanyData->setLeadsCompaniesFieldNull($leads, $field),
            $this->anonymizeContactCompanyData->setLeadsCompaniesFieldNull($companies, $field),
        ];
    }

    /**
     * @param array<Lead>    $leads
     * @param array<Company> $companies
     *
     * @return array<int,array<mixed>>
     */
    private function setHashFields(array $leads, array $companies, LeadField $field, bool $pseudonymize): array
    {
        return [
            $this->anonymizeContactCompanyData->setHashes($leads, $field, $pseudonymize),
            $this->anonymizeContactCompanyData->setHashes($companies, $field, $pseudonymize),
        ];
    }
}
