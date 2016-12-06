<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

/**
 * IpAddressRepository.
 */
class IpAddressRepository extends CommonRepository
{
    /**
     * Count how many unique IP addresses is there.
     *
     * @return int
     */
    public function countIpAddresses()
    {
        $q = $this->createQueryBuilder('i');
        $q->select('COUNT(DISTINCT i.id) as unique');
        $results = $q->getQuery()->getSingleResult();

        if (!isset($results['unique'])) {
            return 0;
        }

        return (int) $results['unique'];
    }
}
