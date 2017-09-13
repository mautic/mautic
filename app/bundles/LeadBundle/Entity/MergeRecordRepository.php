<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class MergeRecordRepository.
 */
class MergeRecordRepository extends CommonRepository
{
    /**
     * @param $id
     *
     * @return null|Lead
     */
    public function findMergedContact($id)
    {
        /** @var MergeRecord $record */
        if ($record = $this->findOneBy(['mergedId' => (int) $id], ['dateAdded' => 'desc'])) {
            $contact = $record->getContact();

            // Clear these records from the EM so that subsequent fetches don't return deleted entities
            $this->getEntityManager()->clear(MergeRecord::class);

            return $contact;
        }

        return null;
    }

    /**
     * Keep track of subseqent merges by cascading records to the latest lead that was merged into.
     *
     * @param $fromId
     * @param $toId
     */
    public function moveMergeRecord($fromId, $toId)
    {
        $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'contact_merge_records')
            ->set('contact_id', (int) $toId)
            ->where('contact_id = '.(int) $fromId)
            ->execute();
    }
}
