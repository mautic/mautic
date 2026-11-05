<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<MergeRecord>
 */
final class MergeRecordRepository extends CommonRepository
{
    public function findMergedContact($id): ?Lead
    {
        $record = $this->findOneBy(['mergedId' => (int) $id], ['dateAdded' => 'desc']);
        if ($record instanceof MergeRecord) {
            $contact = $record->getContact();

            // Clear these records from the EM so that subsequent fetches don't return deleted entities
            $this->getEntityManager()->detach($record);

            return $contact;
        }

        return null;
    }

    /**
     * Keep track of subseqent merges by cascading records to the latest lead that was merged into.
     */
    public function moveMergeRecord(int $fromId, int $toId): void
    {
        $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'contact_merge_records')
            ->set('contact_id', $toId)
            ->where('contact_id = '.$fromId)
            ->executeQuery();
    }
}
