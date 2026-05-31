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

    public function __construct(
        private CompanyModel $companyModel,
        private LeadModel $leadModel,
        private CorePermissions $security,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $assignments
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
        $pairsToAssign = [];

        foreach ($dedupedForProcessing as $entry) {
            [$contactId, $companyId] = self::parsePair($entry);
            $pairKey                 = self::pairKey($contactId, $companyId);

            if ($contactId <= 0) {
                $outcomes[$pairKey] = self::resultEntry($contactId, $companyId, Response::HTTP_NOT_FOUND, self::MESSAGE_CONTACT_NOT_FOUND);
                continue;
            }

            if ($companyId <= 0) {
                $outcomes[$pairKey] = self::resultEntry($contactId, $companyId, Response::HTTP_NOT_FOUND, self::MESSAGE_COMPANY_NOT_FOUND);
                continue;
            }

            $contactIds[$contactId] = $contactId;
            $companyIds[$companyId] = $companyId;
            $pairsToAssign[]        = ['contactId' => $contactId, 'companyId' => $companyId];
        }

        $contactsById  = $this->loadContactsById(array_values($contactIds));
        $companiesById = $this->loadCompaniesById(array_values($companyIds));

        /** @var array<int, list<int>> $companyIdsByContact */
        $companyIdsByContact = [];

        foreach ($pairsToAssign as $pair) {
            $contactId = $pair['contactId'];
            $companyId = $pair['companyId'];
            $pairKey   = self::pairKey($contactId, $companyId);

            if (isset($outcomes[$pairKey])) {
                continue;
            }

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

        foreach ($companyIdsByContact as $contactId => $idsForContact) {
            $contact = $contactsById[$contactId];

            try {
                $this->companyModel->addLeadToCompany($idsForContact, $contact);
                $status  = Response::HTTP_OK;
                $message = self::MESSAGE_ADDED;
            } catch (\Throwable) {
                $status  = Response::HTTP_INTERNAL_SERVER_ERROR;
                $message = self::MESSAGE_UNEXPECTED;
            }

            foreach ($idsForContact as $companyId) {
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
     * @return list<array{contactId: int, companyId: int}>
     */
    public static function dedupeAssignments(array $assignments): array
    {
        $deduped = [];
        $seen    = [];

        foreach ($assignments as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            [$contactId, $companyId] = self::parsePair($entry);
            $key                     = self::pairKey($contactId, $companyId);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduped[]  = ['contactId' => $contactId, 'companyId' => $companyId];
        }

        return $deduped;
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return array{0: int, 1: int}
     */
    public static function parsePair(array $entry): array
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

        $contacts = $this->leadModel->getRepository()->findBy(['id' => $ids]);
        $indexed  = [];

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

        $companies = $this->companyModel->getRepository()->findBy(['id' => $ids]);
        $indexed   = [];

        foreach ($companies as $company) {
            \assert($company instanceof Company);
            $indexed[$company->getId()] = $company;
        }

        return $indexed;
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
