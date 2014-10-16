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
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLogForObject($object, $id)
    {
        $logs = $this->createQueryBuilder('al')
            ->select('al.userName, al.userId, al.action, al.details, al.dateAdded, al.ipAddress')
            ->where('al.object = :object')
            ->andWhere('al.objectId = :id')
            ->setParameter('object', $object)
            ->setParameter('id', $id)
            ->orderBy('al.dateAdded', 'DESC')
            ->getQuery()
            ->getArrayResult();

        // Format log details which are not strings
        foreach ($logs as &$log) {
        	foreach ($log['details'] as &$detail) {
        		if (is_object($detail[1]) && get_class($detail[1]) == 'DateTime') {
        			$detail[1] = $detail[1]->format('D, d M Y H:i:s');
        		}

        		if ($detail[1] === true) {
        			$detail[1] = 'mautic.core.form.yes';
        		}

        		if ($detail[1] === false) {
        			$detail[1] = 'mautic.core.form.no';
        		}
        	}
        }

        return $logs;
    }
}
