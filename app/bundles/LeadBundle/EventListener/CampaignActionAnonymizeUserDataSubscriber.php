<?php

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\EmailBundle\Model\EmailStatModel;
use Mautic\LeadBundle\Entity\Company;
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

    public function __construct(
        private LeadModel $leadModel,
        private FieldModel $fieldModel,
        private CompanyModel $companyModel,
        private LoggerInterface $logger,
        private EmailStatModel $emailStatModel,
        private EntityManager $entityManager
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

        $properties       = $event->getEvent()->getProperties();
        $pseudonymize     = $properties['pseudonymize'] ?? false;
        $leads            = $this->leadModel->getRepository()->findBy(['id' => $event->getContactIds()]);
        $companies        = $this->getCompaniesByLeads($event->getContactIds());

        $idFields   = array_merge($properties['fieldsToAnonymize'], $properties['fieldsToDelete']);
        $fields     = $this->fieldModel->getRepository()->findBy(['id' => $idFields]);

        foreach ($fields as $field) {
            if (in_array($field->getId(), $properties['fieldsToDelete'])) {
                [$leads,$companies] = $this->setDeleteFields($leads, $companies, $field);
                continue;
            }

            if (in_array($field->getId(), $properties['fieldsToAnonymize'])) {
                $leadsCompanyColumnsLength = $this->getLeadCompanyColumnsLenght();
                [$leads,$companies]        = $this->setHashFields($leads, $companies, $field, $pseudonymize, $leadsCompanyColumnsLength);
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
    private function setHashFields(array $leads, array $companies, LeadField $field, bool $pseudonymize, array $leadsCompanyColumnsLength): array
    {
        return [
            $this->setHashes($leads, $field, $pseudonymize, $leadsCompanyColumnsLength),
            $this->setHashes($companies, $field, $pseudonymize, $leadsCompanyColumnsLength),
        ];
    }

    /**
     * @param array<Lead>|array<Company> $leadsCompanies
     *
     * @return array<mixed>
     */
    private function setHashes(array $leadsCompanies, LeadField $field, bool $pseudonymize, array $leadsCompanyColumnsLength): array
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

            $leadsCompanies[$key] = $this->setHash($leadCompany, $leadField, $field, $pseudonymize, $leadsCompanyColumnsLength);
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
        array $leadsCompanyColumnsLength
    ): Lead|Company {
        if (empty($field['value'])) {
            return $leadOrCompany;
        }

        try {
            if ('email' === $field['type']) {
                $valueAnonymized = AnonymizeHelper::email($field['value'], $pseudonymize);
                $this->updateEmailStatusValues($field['value'], $valueAnonymized, $pseudonymize);
                $this->updateFormResultsTable($field['value'], $valueAnonymized, $pseudonymize);
            } else {
                $valueAnonymized = AnonymizeHelper::text($field['value'], $pseudonymize);
            }

            if ($this->getCharLengthLimit($leadField, $leadsCompanyColumnsLength) >= strlen($valueAnonymized)) {
                $leadOrCompany->addUpdatedField($leadField->getAlias(), $valueAnonymized, $pseudonymize);
            }
        } catch (\Exception $e) {
            // Do nothing
            $this->logger->error('AnonymizeUserDataSubscriber setHash fail: '.$e->getMessage());
        }

        return $leadOrCompany;
    }

    private function getCharLengthLimit(LeadField $leadField, array $leadsCompanyColumnsLength): int
    {
        $alias = $leadField->getAlias();
        $key   = 'companies';
        if ('lead' === $leadField->getObject()) {
            $key = 'leads';
        }
        if (isset($leadsCompanyColumnsLength[$key][$alias])) {
            return $leadsCompanyColumnsLength[$key][$alias];
        }

        return $leadField->getCharLengthLimit();
    }

    private function getLeadCompanyColumnsLenght(): array
    {
        $leadMetadata    = $this->entityManager->getClassMetadata(Lead::class);
        $companyMetadata = $this->entityManager->getClassMetadata(Company::class);
        $columnsLength   = [
            'leads'     => [],
            'companies' => [],
        ];
        foreach ($leadMetadata->fieldMappings as $fieldName => $fieldMapping) {
            if (isset($fieldMapping['length'])) {
                $columnsLength['leads'][$fieldName] = $fieldMapping['length'];
            }
        }

        foreach ($companyMetadata->fieldMappings as $fieldName => $fieldMapping) {
            if (isset($fieldMapping['length'])) {
                $columnsLength['companies'][$fieldName] = $fieldMapping['length'];
            }
        }

        return $columnsLength;
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

    private function updateFormResultsTable(string $email, string $hash, bool $pseudonymize): void
    {
        $connection = $this->entityManager->getConnection();
        $prefix     = MAUTIC_TABLE_PREFIX;
        // Step 1: Get all tables starting with 'form_results_'
        $query  = "SHOW TABLES LIKE '".$prefix."form_results_%'";
        $tables = $connection->executeQuery($query)->fetchAllAssociative();

        $resultTablesWithEmail = [];

        foreach ($tables as $table) {
            // Step 2: Extract table name (key depends on your database)
            $tableName = reset($table); // Ensure to get the table name only

            // Step 3: Check if email exists in the current table
            $columnCheckQuery = "SHOW COLUMNS FROM `$tableName`";
            $columns          = $connection->executeQuery($columnCheckQuery)->fetchAllAssociative();

            foreach ($columns as $column) {
                if (str_contains($column['Field'], 'email')) {
                    $resultTablesWithEmail[] = [
                        'table'     => $table,
                        'tableName' => $tableName,
                        'column'    => $column,
                    ];
                }
            }
        }

        if (empty($resultTablesWithEmail)) {
            return;
        }

        foreach ($resultTablesWithEmail as $table) {
            if (!in_array($table['column']['Type'], self::COLUMNS_ACEPPTED)) {
                continue;
            }

            if ($pseudonymize) {
                $updateQuery = "UPDATE `{$table['tableName']}` SET `{$table['column']['Field']}` = :hash WHERE `{$table['column']['Field']}` = :email";
                $connection->executeQuery($updateQuery, ['hash' => $hash, 'email' => $email]);
                continue;
            }

            $selectQuery = "SELECT `{$table['column']['Field']}`, submission_id FROM `{$table['tableName']}` WHERE `{$table['column']['Field']}` = :email";
            $result      = $connection->executeQuery($selectQuery, ['email' => $email])->fetchAllAssociative();

            if (empty($result)) {
                continue;
            }

            foreach ($result as $row) {
                $hash        = AnonymizeHelper::email($email, $pseudonymize);
                $updateQuery = "UPDATE `{$table['tableName']}` SET `{$table['column']['Field']}` = :hash WHERE `submission_id` = :submission_id";
                $connection->executeQuery($updateQuery, ['hash' => $hash, 'submission_id' => $row['submission_id']]);
            }
        }
    }
}
