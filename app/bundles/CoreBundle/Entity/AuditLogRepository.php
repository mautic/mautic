<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
     * @param string $object
     * @param integer $id
     * @param intener $limit of items
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLogForObject($object = null, $id = null, $limit = 100)
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

        $query->orderBy('al.dateAdded', 'DESC')
            ->setMaxResults($limit);

        $logs = $query->getQuery()
            ->getArrayResult();

        return $logs;
    }
}
