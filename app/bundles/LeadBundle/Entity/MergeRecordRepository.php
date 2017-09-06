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
        /** @var MergeRecord[] $entities */
        $entities = $this->findBy(['mergedId' => $id]);

        if (count($entities)) {
            return $entities[0]->getContact();
        }

        return null;
    }
}
