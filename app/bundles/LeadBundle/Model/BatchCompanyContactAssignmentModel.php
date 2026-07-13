<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;

class BatchCompanyContactAssignmentModel
{
    private const MESSAGE_ADDED             = 'Contact added to company';

    private const MESSAGE_CONTACT_NOT_FOUND = 'Contact not found';

    private const MESSAGE_COMPANY_NOT_FOUND = 'Company not found';

    private const MESSAGE_ACCESS_DENIED     = 'Access denied';

    private const MESSAGE_UNEXPECTED        = 'An unexpected error occurred';

    private const LOG_TYPE                  = 'api';

    private const LOG_EVENT_NAME_BATCH      = 'API batch assignment';

    private const LOG_EVENT_NAME_SINGLE     = 'API assignment';

    private const LOG_ACTION_PREFIX         = 'Lead added to the company, ';

    public function __construct(
        private readonly CompanyModel $companyModel,
        private readonly LeadModel $leadModel,
        private readonly CorePermissions $security,
    ) {
    }

    /**
     * @param non-empty-array<int, array<string, mixed>> $assignments
     *
     * @return array{results: list<array{contactId: int, companyId: int, status: int, message: string}>, summary: array{total: int, succeeded: int, failed: int}}
     */
    public function process(array $assignments): array
    {
        $dedupedForProcessing = self::dedupeAssignments($assignments);

        /** @var array<string, array{contactId: int, companyId: int, status: int, message: string}> $outcomes */
        $outcomes = [];

        $contactIds    = [];
        $companyIds    = [];
        /** @var array<string, array{contactId: int, companyId: int}> $pairsToAssign */
        $pairsToAssign = [];

        foreach ($dedupedForProcessing as $pairKey => $entry) {
            [$contactId, $companyId] = self::parsePair($entry);

            if ($contactId <= 0) {
                $outcomes[$pairKey] = self::resultEntry($contactId, $companyId, Response::HTTP_NOT_FOUND, self::MESSAGE_CONTACT_NOT_FOUND);
                continue;
            }

            if ($companyId <= 0) {
                $outcomes[$pairKey] = self::resultEntry($contactId, $companyId, Response::HTTP_NOT_FOUND, self::MESSAGE_COMPANY_NOT_FOUND);
                continue;
            }

            $contactIds[$contactId]  = $contactId;
            $companyIds[$companyId]  = $companyId;
            $pairsToAssign[$pairKey] = ['contactId' => $contactId, 'companyId' => $companyId];
        }

        $contactsById  = $this->loadContactsById(array_values($contactIds));
        $companiesById = $this->loadCompaniesById(array_values($companyIds));

        /** @var array<int, list<int>> $companyIdsByContact */
        $companyIdsByContact = [];

        foreach ($pairsToAssign as $pairKey => $pair) {
            if (isset($outcomes[$pairKey])) {
                continue;
            }

            $contactId = $pair['contactId'];
            $companyId = $pair['companyId'];

            $contact = $contactsById[$contactId] ?? null;
            if (!$contact instanceof Lead) {
                $outcomes[$pairKey] = self::resultEntry($contactId, $companyId, Response::HTTP_NOT_FOUND, self::MESSAGE_CONTACT_NOT_FOUND);
                continue;
            }

            if (!isset($companiesById[$companyId])) {
                $outcomes[$pairKey] = self::resultEntry($contactId, $companyId, Response::HTTP_NOT_FOUND, self::MESSAGE_COMPANY_NOT_FOUND);
                continue;
            }

            if (!$this->security->hasEntityAccess(
                'lead:leads:editown',
                'lead:leads:editother',
                $contact->getPermissionUser()
            )) {
                $outcomes[$pairKey] = self::resultEntry($contactId, $companyId, Response::HTTP_FORBIDDEN, self::MESSAGE_ACCESS_DENIED);
                continue;
            }

            $companyIdsByContact[$contactId][] = $companyId;
        }

        foreach ($companyIdsByContact as $contactId => $companyIdsForContact) {
            $contact = $contactsById[$contactId];
            sort($companyIdsForContact);

            try {
                $addedCompanyIds = $this->companyModel->addLeadToCompany($companyIdsForContact, $contact);
                $status          = Response::HTTP_OK;
                $message         = self::MESSAGE_ADDED;
            } catch (\Throwable) {
                $status  = Response::HTTP_INTERNAL_SERVER_ERROR;
                $message = self::MESSAGE_UNEXPECTED;
            }

            if (Response::HTTP_OK === $status) {
                try {
                    $this->logContactCompanyAssignments($contact, $addedCompanyIds, $companiesById, self::LOG_EVENT_NAME_BATCH);
                } catch (\Throwable) {
                    // Assignment succeeded; logging failure must not change the API outcome.
                }
            }

            foreach ($companyIdsForContact as $companyId) {
                $outcomes[self::pairKey($contactId, $companyId)] = self::resultEntry($contactId, $companyId, $status, $message);
            }
        }

        $results   = [];
        $succeeded = 0;
        $failed    = 0;

        foreach ($assignments as $entry) {
            if (!is_array($entry)) {
                $results[] = self::resultEntry(0, 0, Response::HTTP_NOT_FOUND, self::MESSAGE_CONTACT_NOT_FOUND);
                ++$failed;
                continue;
            }

            [$contactId, $companyId] = self::parsePair($entry);
            $pairKey                 = self::pairKey($contactId, $companyId);
            $result                  = $outcomes[$pairKey] ?? self::resultEntry($contactId, $companyId, Response::HTTP_NOT_FOUND, self::MESSAGE_CONTACT_NOT_FOUND);
            $results[]               = $result;

            if (Response::HTTP_OK === $result['status']) {
                ++$succeeded;
            } else {
                ++$failed;
            }
        }

        return [
            'results' => $results,
            'summary' => [
                'total'     => count($assignments),
                'succeeded' => $succeeded,
                'failed'    => $failed,
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $assignments
     *
     * @return array<string, array{contactId: int, companyId: int}>
     */
    private static function dedupeAssignments(array $assignments): array
    {
        /** @var array<string, array{contactId: int, companyId: int}> $deduped */
        $deduped = [];

        foreach ($assignments as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            [$contactId, $companyId] = self::parsePair($entry);
            $key                     = self::pairKey($contactId, $companyId);

            if (isset($deduped[$key])) {
                continue;
            }

            $deduped[$key] = ['contactId' => $contactId, 'companyId' => $companyId];
        }

        return $deduped;
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return array{0: int, 1: int}
     */
    private static function parsePair(array $entry): array
    {
        return [
            (int) ($entry['contactId'] ?? 0),
            (int) ($entry['companyId'] ?? 0),
        ];
    }

    private static function pairKey(int $contactId, int $companyId): string
    {
        return $contactId.':'.$companyId;
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int, Lead>
     */
    private function loadContactsById(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $contacts = $this->leadModel->getEntities([
            'ids'            => $ids,
            'iterable_mode'  => true,
        ]);
        $indexed = [];

        foreach ($contacts as $contact) {
            \assert($contact instanceof Lead);
            $indexed[$contact->getId()] = $contact;
        }

        return $indexed;
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int, Company>
     */
    private function loadCompaniesById(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $companies = $this->companyModel->getEntities([
            'ids'            => $ids,
            'iterable_mode'  => true,
        ]);
        $indexed = [];

        foreach ($companies as $company) {
            \assert($company instanceof Company);
            $indexed[$company->getId()] = $company;
        }

        return $indexed;
    }

    /**
     * @param list<int>           $addedCompanyIds
     * @param array<int, Company> $companiesById
     */
    public function logContactCompanyAssignments(Lead $contact, array $addedCompanyIds, array $companiesById, string $eventName = self::LOG_EVENT_NAME_SINGLE): void
    {
        if ([] === $addedCompanyIds) {
            return;
        }

        foreach ($addedCompanyIds as $companyId) {
            $company = $companiesById[$companyId] ?? null;
            if (!$company instanceof Company) {
                continue;
            }

            $contact->addCompanyChangeLogEntry(
                self::LOG_TYPE,
                $eventName,
                self::LOG_ACTION_PREFIX.$company->getName(),
                $companyId
            );
        }

        $this->leadModel->getRepository()->saveEntity($contact);
    }

    /**
     * @return array{contactId: int, companyId: int, status: int, message: string}
     */
    private static function resultEntry(int $contactId, int $companyId, int $status, string $message): array
    {
        return [
            'contactId' => $contactId,
            'companyId' => $companyId,
            'status'    => $status,
            'message'   => $message,
        ];
    }
}
