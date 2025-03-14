<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Form\Type\CampaignActionAnonymizeUserDataType;
use Mautic\LeadBundle\Helper\AnonymizeHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionAnonymizeUserDataSubscriber implements EventSubscriberInterface
{
    public const KEY_EVENT_NAME = 'lead.action_anonymizeuserdata';

    public function __construct(
        private LeadModel $leadModel,
        private FieldModel $fieldModel,
        private CompanyModel $companyModel
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                  => ['configureAction', 0],
            LeadEvents::ON_CAMPAIGN_ACTION_ANONYMIZE_USER_DATA => ['anonymizeUserData', 0],
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
                'eventName'              => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
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

        $properties = $event->getEvent()->getProperties();
        if (empty($properties['pseudonymize'])) {
            return;
        }

        $leads      = $this->leadModel->getEntities($event->getContactIds());
        $companies  = $this->getCompaniesByLeads($event->getContactIds());

        $idFields   = array_merge($properties['fieldsToAnonymize'], $properties['fieldsToDelete']);
        $fields     = $this->fieldModel->getRepository()->findBy(['id' => $idFields]);

        foreach ($fields as $field) {
            if (in_array($field->getId(), $properties['fieldsToDelete'])) {
                [$leads,$companies] = $this->setDeleteFields($leads, $companies, $field);
                continue;
            }

            if (in_array($field->getId(), $properties['fieldsToAnonymize'])) {
                [$leads,$companies] = $this->setHashFields($leads, $companies, $field);
            }
        }

        if (!empty($leads)) {
            $this->leadModel->saveEntities($leads);
        }

        if (!empty($companies)) {
            $this->companyModel->saveEntities($companies);
        }

        $event->passAll();
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

        return $this->companyModel->getEntities($companiesId);
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
            $this->setLeadsCompaniesFieldNull($leads, $field),
            $this->setLeadsCompaniesFieldNull($companies, $field),
        ];
    }

    /**
     * @param array<Lead|Company> $leadsCompanies
     *
     * @return array<mixed>
     */
    private function setLeadsCompaniesFieldNull(array $leadsCompanies, LeadField $field): array
    {
        foreach ($leadsCompanies as $key => $leadCompany) {
            if (!method_exists($leadCompany, 'addUpdatedField') || !method_exists($leadCompany, 'getField')) {
                continue;
            }

            $leadField = $leadCompany->getField($field->getAlias());
            if (false === $leadField) {
                continue;
            }
            $leadsCompanies[$key] = $leadCompany->addUpdatedField($field->getAlias(), null);
        }

        return $leadsCompanies;
    }

    /**
     * @param array<Lead>    $leads
     * @param array<Company> $companies
     *
     * @return array<int,array<mixed>>
     */
    private function setHashFields(array $leads, array $companies, LeadField $field): array
    {
        return [
            $this->setHashes($leads, $field),
            $this->setHashes($companies, $field),
        ];
    }

    /**
     * @param array<Lead>|array<Company> $leadsCompanies
     *
     * @return array<mixed>
     */
    private function setHashes(array $leadsCompanies, LeadField $field): array
    {
        foreach ($leadsCompanies as $key => $leadCompany) {
            if (!method_exists($leadCompany, 'getField')) {
                continue;
            }
            $leadField = $leadCompany->getField($field->getAlias());
            if (false === $leadField) {
                continue;
            }

            $field     = $this->fieldModel->getRepository()->find($leadField['id']);

            if (null === $field) {
                continue;
            }

            if ($field->getCharLengthLimit() < 64) {
                continue;
            }

            $leadsCompanies[$key] = $this->setHash($leadCompany, $leadField, $field);
        }

        return $leadsCompanies;
    }

    /**
     * @param array<string, string|null> $field
     */
    private function setHash(Company|Lead $leadOrCompany, array $field, LeadField $leadField): Lead|Company
    {
        if (empty($field['value'])) {
            return $leadOrCompany;
        }

        if ('email' === $field['type']) {
            $leadOrCompany->addUpdatedField($leadField->getAlias(), AnonymizeHelper::email($field['value']));
        } else {
            $leadOrCompany->addUpdatedField($leadField->getAlias(), AnonymizeHelper::text($field['value']));
        }

        return $leadOrCompany;
    }
}
