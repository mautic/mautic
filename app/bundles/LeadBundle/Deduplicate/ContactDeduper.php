<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Deduplicate;

use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class ContactDeduper
{
    use DeduperTrait;

    private ContactMerger $contactMerger;

    private LeadRepository $leadRepository;

    public function __construct(FieldModel $fieldModel, ContactMerger $contactMerger, LeadRepository $leadRepository)
    {
        $this->fieldModel     = $fieldModel;
        $this->contactMerger  = $contactMerger;
        $this->leadRepository = $leadRepository;
    }

    /**
     * @return array<string,string>
     */
    public function getUniqueFields(string $object): array
    {
        return $this->fieldModel->getUniqueIdentifierFields(['object' => $object]);
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
     */
    public function getOneDuplicateContact(array $uniqueFieldAliases): ?Lead
    {
        if (!$contactId = $this->leadRepository->getOneDuplicatedContactId($uniqueFieldAliases)) {
            return null;
        }

        return $this->leadRepository->getEntity($contactId);
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
            } catch (SameContactException $exception) {
            }

            $loser = $winner;
        }
    }

    /**
     * @deprecated Use the other methods in this service to compose what you need. See DeduplicateCommand for an example.
     *
     * @param bool $mergeNewerIntoOlder
     *
     * @return int
     */
    public function deduplicate($mergeNewerIntoOlder = false, OutputInterface $output = null)
    {
        $lastContactId             = 0;
        $totalContacts             = $this->leadRepository->getIdentifiedContactCount();
        $progress                  = null;

        if ($output) {
            $progress = new ProgressBar($output, $totalContacts);
        }

        $dupCount = 0;
        while ($contact = $this->leadRepository->getNextIdentifiedContact($lastContactId)) {
            $lastContactId = $contact->getId();
            $fields        = $contact->getProfileFields();
            $duplicates    = $this->checkForDuplicateContacts($fields, $mergeNewerIntoOlder);

            if ($progress) {
                $progress->advance();
            }

            // Were duplicates found?
            if (count($duplicates) > 1) {
                $loser = reset($duplicates);
                while ($winner = next($duplicates)) {
                    try {
                        $this->contactMerger->merge($winner, $loser);

                        ++$dupCount;

                        if ($progress) {
                            // Advance the progress bar for the deleted contacts that are no longer in the total count
                            $progress->advance();
                        }
                    } catch (SameContactException $exception) {
                    }

                    $loser = $winner;
                }
            }

            // Clear all entities in memory for RAM control
            $this->leadRepository->clear();
            gc_collect_cycles();
        }

        return $dupCount;
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
