<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Deduplicate;

use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class ContactDeduper
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var ContactMerger
     */
    private $contactMerger;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var array
     */
    private $availableFields;

    /**
     * @var bool
     */
    private $mergeNewerIntoOlder = false;

    /**
     * DedupModel constructor.
     *
     * @param FieldModel     $fieldModel
     * @param ContactMerger  $contactMerger
     * @param LeadRepository $leadRepository
     */
    public function __construct(FieldModel $fieldModel, ContactMerger $contactMerger, LeadRepository $leadRepository)
    {
        $this->fieldModel     = $fieldModel;
        $this->contactMerger  = $contactMerger;
        $this->leadRepository = $leadRepository;
    }

    /**
     * @param bool                 $mergeNewerIntoOlder
     * @param OutputInterface|null $output
     *
     * @return int
     */
    public function deduplicate($mergeNewerIntoOlder = false, OutputInterface $output = null)
    {
        $this->mergeNewerIntoOlder = $mergeNewerIntoOlder;
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
            $duplicates    = $this->checkForDuplicateContacts($fields);

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
     * @param array $queryFields
     *
     * @return Lead[]
     */
    public function checkForDuplicateContacts(array $queryFields)
    {
        $duplicates = [];
        if ($uniqueData = $this->getUniqueData($queryFields)) {
            $duplicates = $this->leadRepository->getLeadsByUniqueFields($uniqueData);

            // By default, duplicates are ordered by newest first
            if (!$this->mergeNewerIntoOlder) {
                // Reverse the array so that oldest are on "top" in order to merge oldest into the next until they all have been merged into the
                // the newest record
                $duplicates = array_reverse($duplicates);
            }
        }

        return $duplicates;
    }

    /**
     * @param array $queryFields
     *
     * @return array
     */
    public function getUniqueData(array $queryFields)
    {
        $uniqueLeadFields    = $this->fieldModel->getUniqueIdentifierFields();
        $uniqueLeadFieldData = [];
        $inQuery             = array_intersect_key($queryFields, $this->getAvailableFields());
        foreach ($inQuery as $k => $v) {
            // Don't use empty values when checking for duplicates
            if (empty($v)) {
                continue;
            }

            if (array_key_exists($k, $uniqueLeadFields)) {
                $uniqueLeadFieldData[$k] = $v;
            }
        }

        return $uniqueLeadFieldData;
    }

    /**
     * @return array
     */
    private function getAvailableFields()
    {
        if (null === $this->availableFields) {
            $this->availableFields = $this->fieldModel->getFieldList(
                false,
                false,
                [
                    'isPublished' => true,
                ]
            );
        }

        return $this->availableFields;
    }
}
