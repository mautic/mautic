<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

/**
 * AuditLogRepository
 */
class AuditLogRepository extends CommonRepository
{
    /**
     * Get array of objects which belongs to the object
     *
     * @param null $object
     * @param null $id
     * @param int  $limit
     * @param null $afterDate
     * @param null $bundle
     *
     * @return array
     */
    public function getLogForObject($object = null, $id = null, $limit = 10, $afterDate = null, $bundle = null)
    {
        $query = $this->createQueryBuilder('al')
            ->select('al.userName, al.userId, al.bundle, al.object, al.objectId, al.action, al.details, al.dateAdded, al.ipAddress')
            ->where('al.object != :category')
            ->setParameter('category', 'category');

        if ($object && $id) {
            $query
                ->andWhere('al.object = :object')
                ->andWhere('al.objectId = :id')
                ->setParameter('object', $object)
                ->setParameter('id', $id);
        }

        if ($bundle) {
            $query->andWhere('al.bundle = :bundle')
                ->setParameter('bundle', $bundle);
        }

        // Prevent InnoDB shared IDs
        if ($afterDate) {
            $query->andWhere(
                $query->expr()->gte('al.dateAdded', ':date')
            )
                ->setParameter('date', $afterDate);
        }

        $query->orderBy('al.dateAdded', 'DESC')
            ->setMaxResults($limit);

        return $query->getQuery()->getArrayResult();
    }
}
