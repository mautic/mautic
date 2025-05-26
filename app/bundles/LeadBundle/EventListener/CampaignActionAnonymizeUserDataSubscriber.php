<?php

namespace Mautic\LeadBundle\EventListener;

use Doctrine\DBAL\Types\IntegerType;
use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Model\EmailStatModel;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Form\Type\CampaignActionAnonymizeUserDataType;
use Mautic\LeadBundle\Helper\AnonymizeHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionAnonymizeUserDataSubscriber implements EventSubscriberInterface
{
    public const KEY_EVENT_NAME = 'lead.action_anonymizeuserdata';

    public const COLUMNS_ACEPPTED = ['text', 'longtext'];

    public const COLUMNS_NOT_ACCEPTED = ['submission_id', 'form_id'];

    public const COMPANY_FIELDS_TO_COLUMNS =
        [
            'companyaddress1'    => 'address1',
            'companyaddress2'    => 'address2',
            'companycity'        => 'city',
            'companystate'       => 'state',
            'companyzip'         => 'zip',
            'companycountry'     => 'country',
            'companyphone'       => 'phone',
            'companyfax'         => 'fax',
            'companywebsite'     => 'website',
            'companyemail'       => 'email',
            'companyname'        => 'name',
            'companydescription' => 'description',
            'companyindustry'    => 'industry',
            'companyemployees'   => 'employees',
            'companyrevenue'     => 'revenue',
            'companystatus'      => 'status',
            'companytype'        => 'type',
            'companyalias'       => 'alias',
        ];

    public function __construct(
        private LeadModel $leadModel,
        private FieldModel $fieldModel,
        private CompanyModel $companyModel,
        private LoggerInterface $logger,
        private EmailStatModel $emailStatModel,
        private EntityManager $entityManager,
        private AuditLogModel $auditLogModel,
        private SubmissionModel $submissionModel,
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
        $pseudonymize     = $properties['pseudonymize'] ?? false;
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
            $this->updateFormResults($event, $pseudonymize);
        }

        if (!empty($leads)) {
            $this->leadModel->saveEntities($leads);
        }

        if (!empty($companies)) {
            $this->companyModel->saveEntities($companies);
        }

        $event->passAll();

        if ($deleteFormResultsAndAuditLog) {
            $this->deleteAuditLog($event);
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
            if ($leadCompany instanceof Lead && 'lead' === $field->getObject()) {
                $leadField = $leadCompany->getField($field->getAlias());
                if (false !== $leadField) {
                    $leadCompany->addUpdatedField($field->getAlias(), null);
                    $leadsCompanies[$key] = $leadCompany;
                    continue;
                }
            }
            if ($leadCompany instanceof Company && 'company' === $field->getObject()) {
                $fields    = $leadCompany->getFields();
                $leadField = $leadCompany->getField($field->getAlias());

                if (false !== $leadField) {
                    $leadCompany->addUpdatedField($field->getAlias(), null);
                    $leadsCompanies[$key] = $leadCompany;
                    continue;
                }

                $alias = self::COMPANY_FIELDS_TO_COLUMNS[$field->getAlias()] ?? $field->getAlias();

                $leadFieldValue = $leadCompany->getFieldValue($alias);
                if (property_exists($leadCompany, $alias) && null !== $leadFieldValue) {
                    $leadFieldValue = $leadCompany->{'get'.ucfirst($alias)}();
                    if (null !== $leadFieldValue) {
                        $leadCompany->{'set'.ucfirst($alias)}(null);
                    }
                    continue;
                }
            }
        }

        return $leadsCompanies;
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
            $this->setHashes($leads, $field, $pseudonymize),
            $this->setHashes($companies, $field, $pseudonymize),
        ];
    }

    /**
     * @param array<Lead>|array<Company> $leadsCompanies
     *
     * @return array<mixed>
     */
    private function setHashes(array $leadsCompanies, LeadField $field, bool $pseudonymize): array
    {
        foreach ($leadsCompanies as $key => $leadCompany) {
            if (!method_exists($leadCompany, 'getField')) {
                continue;
            }
            $leadField = false;
            if ($leadCompany instanceof Company && 'company' === $field->getObject()) {
                $leadField = $leadCompany->getField($field->getAlias());
                if (false === $leadField) {
                    $alias          = self::COMPANY_FIELDS_TO_COLUMNS[$field->getAlias()] ?? $field->getAlias();
                    $leadFieldValue = $leadCompany->getFieldValue($alias);
                    if (property_exists($leadCompany, $alias) && null !== $leadFieldValue) {
                        $tempLeadField = $leadCompany->{'get'.ucfirst($alias)}();
                        if (empty($tempLeadField)) {
                            continue;
                        }
                        unset($leadField);
                        $leadField['value'] = $tempLeadField;
                        $leadField['type']  = $field->getType();
                    } else {
                        continue;
                    }
                }
            }
            if ($leadCompany instanceof Lead && 'lead' === $field->getObject()) {
                $leadField = $leadCompany->getField($field->getAlias());
                if (false === $leadField) {
                    continue;
                }

                $field     = $this->fieldModel->getRepository()->find($leadField['id']);

                if (null === $field) {
                    continue;
                }
            }
            if (false === $leadField) {
                continue;
            }

            $leadsCompanies[$key] = $this->setHash($leadCompany, $leadField, $field, $pseudonymize);
        }

        return $leadsCompanies;
    }

    /**
     * @param array<string, string|null> $field
     */
    private function setHash(
        Company|Lead $leadOrCompany,
        array $field,
        LeadField $leadField,
        bool $pseudonymize,
    ): Lead|Company {
        if (empty($field['value'])) {
            return $leadOrCompany;
        }

        try {
            if ('email' === $field['type']) {
                $valueAnonymized = AnonymizeHelper::email($field['value'], $pseudonymize);
                if ($leadField->getCharLengthLimit() < strlen($valueAnonymized)) {
                    $valueAnonymized = $this->formatHashEmail($valueAnonymized, $leadField->getCharLengthLimit());
                }
                $this->updateEmailStatusValues($field['value'], $valueAnonymized, $pseudonymize);
            } else {
                $valueAnonymized = AnonymizeHelper::text($field['value'], $pseudonymize);
            }

            if ($leadField->getCharLengthLimit() < strlen($valueAnonymized) && 'email' === $field['type']) {
                $valueAnonymized = $this->formatHashEmail($valueAnonymized, $leadField->getCharLengthLimit());
            } elseif ($leadField->getCharLengthLimit() < strlen($valueAnonymized)) {
                $valueAnonymized = substr($valueAnonymized, 0, $leadField->getCharLengthLimit());
            }

            if ($leadOrCompany instanceof Lead) {
                $leadOrCompany->addUpdatedField($leadField->getAlias(), $valueAnonymized);
            }

            if ($leadOrCompany instanceof Company) {
                $alias       = self::COMPANY_FIELDS_TO_COLUMNS[$leadField->getAlias()] ?? $leadField->getAlias();
                if (property_exists($leadOrCompany, $alias)) {
                    $leadOrCompany->{'set'.ucfirst($alias)}($valueAnonymized);
                }
            }
        } catch (\Exception $e) {
            // Do nothing
            $this->logger->error('AnonymizeUserDataSubscriber setHash fail: '.$e->getMessage());
        }

        return $leadOrCompany;
    }

    private function formatHashEmail(string $email, int $limit): string
    {
        // Extract the domain from the email
        $atPosition = strrpos($email, '@'); // Find the position of '@'

        if (false === $atPosition) {
            // If the email does not have a domain, return the email as-is
            return $email;
        }

        $domain       = substr($email, $atPosition); // Extract the domain (e.g., @gmail.com or @uol.com)
        $domainLength = strlen($domain);

        // Calculate the allowed length for the local part
        $localPartLength = $limit - $domainLength;

        // If the local part length is less than 1, it's not possible to truncate
        if ($localPartLength < 1) {
            return $email;
        }

        // Extract and truncate the local part
        $localPart = substr($email, 0, $localPartLength);

        // Combine the truncated local part with the domain
        return $localPart.$domain;
    }

    private function updateEmailStatusValues(string $email, string $hash, bool $pseudonymize): void
    {
        // email_stats.email_address
        $emailStats = $this->emailStatModel->getRepository()->findBy(['emailAddress' => $email]);
        foreach ($emailStats as $emailStat) {
            if (!$pseudonymize) {
                $hash = AnonymizeHelper::email($email, $pseudonymize);
            }

            $emailStat->setEmailAddress($hash);
            $this->emailStatModel->saveEntity($emailStat);
        }
    }

    private function updateFormResults(PendingEvent $event, bool $pseudonymize): void
    {
        $leads               = $event->getContacts();
        $valueSubmissionForm = [];
        foreach ($leads as $lead) {
            $submissionForms = $this->submissionModel->getRepository()->findBy(['lead' => $lead]);

            foreach ($submissionForms as $submissionForm) {
                $id                    = $submissionForm->getForm()->getId();
                $alias                 = $submissionForm->getForm()->getAlias();
                if ($pseudonymize) {
                    $newValueSubmissionForm   = $this->getDataFromForm($id, $alias, $submissionForm);
                    $valueSubmissionForm      = array_merge($valueSubmissionForm, $newValueSubmissionForm);
                }

                $this->updateFormResultsByLead($id, $alias, $submissionForm, $valueSubmissionForm);
            }
        }
    }

    private function deleteAuditLog(PendingEvent $event): void
    {
        $leads = $event->getContacts();
        foreach ($leads as $lead) {
            assert($lead instanceof Lead);
            $companyLeads = $this->companyModel->getCompanyLeadRepository()->getEntitiesByLead($lead);
            foreach ($companyLeads as $companyLead) {
                assert($companyLead instanceof CompanyLead);
                $company          = $companyLead->getCompany();
                $auditLogsCompany = $this->auditLogModel->getRepository()->findBy(
                    [
                        'objectId' => $company,
                        'bundle'   => 'lead',
                        'object'   => 'company',
                    ]
                );
                foreach ($auditLogsCompany as $auditLog) {
                    $this->auditLogModel->getRepository()->deleteEntity($auditLog);
                }
            }
            $auditLogs = $this->auditLogModel->getRepository()->findBy(
                [
                    'objectId' => $lead,
                    'bundle'   => 'lead',
                    'object'   => 'lead',
                ]
            );
            foreach ($auditLogs as $auditLog) {
                $this->auditLogModel->getRepository()->deleteEntity($auditLog);
            }
        }
    }

    /**
     * @param array <string,string> $valuesAnonymize
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function updateFormResultsByLead(int $formId, string $formAlias, Submission $submissionForm, array $valuesAnonymize): void
    {
        $connection = $this->entityManager->getConnection();
        $nameTable  = $this->submissionModel->getRepository()->getResultsTableName($formId, $formAlias);
        $columns    = $connection->createSchemaManager()->listTableColumns($nameTable);

        foreach ($columns as $column) {
            // 1 = IntegerType
            if (1 === $column->getType()->getBindingType()) {
                continue;
            }
            $columnsToUpdate[] = $column->getName();
        }
        $results  = $connection->createQueryBuilder()
            ->select('*')
            ->from($nameTable)
            ->where('submission_id = :submissionId')
            ->setParameter('submissionId', $submissionForm->getId())
            ->executeQuery()
            ->fetchAllAssociative();

        $keyValueToChange = [];

        foreach ($results as $resultForm) {
            foreach ($resultForm as $key => $value) {
                if (!in_array($key, $columnsToUpdate)) {
                    continue;
                }

                if (array_key_exists($value, $valuesAnonymize)) {
                    $keyValueToChange[$key] = $valuesAnonymize[$value];
                } else {
                    $keyValueToChange[$key] = AnonymizeHelper::text($value);
                }
            }
        }

        if (empty($keyValueToChange)) {
            return;
        }

        $connection->update(
            $nameTable,
            $keyValueToChange,
            [
                'submission_id' => $submissionForm->getId(),
            ]
        );
    }

    /**
     * @return array<string,string>
     */
    private function getDataFromForm(int $formId, string $formAlias, Submission $submission): array
    {
        $connection = $this->entityManager->getConnection();
        $nameTable  = $this->submissionModel->getRepository()->getResultsTableName($formId, $formAlias);
        $results    = $connection->createQueryBuilder()
            ->select('*')
            ->from($nameTable)
            ->where('submission_id = :submissionId')
            ->setParameter('submissionId', $submission->getId())
            ->executeQuery()
            ->fetchAllAssociative();

        $finalResult = [];
        foreach ($results as $resultForm) {
            foreach ($resultForm as $key => $value) {
                if (!in_array($key, self::COLUMNS_NOT_ACCEPTED)) {
                    $finalResult[$value] = AnonymizeHelper::text($value, true);
                }
            }
        }

        return $finalResult;
    }
}
