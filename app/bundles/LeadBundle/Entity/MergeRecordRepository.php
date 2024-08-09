<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<MergeRecord>
 */
class MergeRecordRepository extends CommonRepository
{
    /**
     * @return Lead|null
     */
    public function findMergedContact($id)
    {
        /** @var MergeRecord $record */
        if ($record = $this->findOneBy(['mergedId' => (int) $id], ['dateAdded' => 'desc'])) {
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
    public function moveMergeRecord($fromId, $toId): void
    {
        $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'contact_merge_records')
            ->set('contact_id', (int) $toId)
            ->where('contact_id = '.(int) $fromId)
            ->executeQuery();
    }
}
