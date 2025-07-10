<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Deduplicate;

use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\FieldModel;

class ContactDeduper
{
    use DeduperTrait;

    public function __construct(
        FieldModel $fieldModel,
        FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier,
        private ContactMerger $contactMerger,
        private LeadRepository $leadRepository,
    ) {
        $this->fieldModel                 = $fieldModel;
        $this->fieldsWithUniqueIdentifier = $fieldsWithUniqueIdentifier;
    }

    /**
     * @return array<string,string>
     */
    public function getUniqueFields(string $object): array
    {
        return $this->fieldsWithUniqueIdentifier->getFieldsWithUniqueIdentifier(['object' => $object]);
    }

    /**
     * @param string[] $uniqueFieldAliases
     */
    public function countDuplicatedContacts(array $uniqueFieldAliases): int
    {
        return $this->leadRepository->getContactCountWithDuplicateValues($uniqueFieldAliases);
    }

    /**
     * @param string[] $uniqueFieldAliases
     *
     * @return string[]
     */
    public function getDuplicateContactIds(array $uniqueFieldAliases): array
    {
        return $this->leadRepository->getDuplicatedContactIds($uniqueFieldAliases);
    }

    /**
     * @param string[]|int[] $contactIds
     *
     * @return Lead[]
     */
    public function getContactsByIds(array $contactIds): array
    {
        return $this->leadRepository->getEntities(['ids' => $contactIds, 'ignore_paginator' => false]);
    }

    /**
     * @param Lead[] $contacts
     */
    public function deduplicateContactBatch(array $contacts, bool $newerIntoOlder, callable $onContactProcessed = null): void
    {
        foreach ($contacts as $contact) {
            $duplicates = $this->checkForDuplicateContacts($contact->getProfileFields(), $newerIntoOlder);

            $this->mergeContacts($duplicates);
            $this->detachContacts($duplicates);

            if ($onContactProcessed) {
                $onContactProcessed($contact);
            }
        }
    }

    /**
     * To save RAM.
     *
     * @param Lead[] $contacts
     */
    public function detachContacts(array $contacts): void
    {
        $this->leadRepository->detachEntities($contacts);
    }

    /**
     * @param Lead[] $duplicates
     */
    public function mergeContacts(array $duplicates): void
    {
        if (empty($duplicates)) {
            return;
        }

        $loser = reset($duplicates);
        while ($winner = next($duplicates)) {
            try {
                $this->contactMerger->merge($winner, $loser);
            } catch (SameContactException) {
            }

            $loser = $winner;
        }
    }

    /**
     * @return Lead[]
     */
    public function checkForDuplicateContacts(array $queryFields, bool $mergeNewerIntoOlder = false)
    {
        $duplicates = [];
        $uniqueData = $this->getUniqueData($queryFields);
        if (!empty($uniqueData)) {
            $duplicates = $this->leadRepository->getLeadsByUniqueFields($uniqueData);

            // By default, duplicates are ordered by newest first
            if (!$mergeNewerIntoOlder) {
                // Reverse the array so that oldest are on "top" in order to merge oldest into the next until they all have been merged into the
                // the newest record
                $duplicates = array_reverse($duplicates);
            }
        }

        return $duplicates;
    }
}
