<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class PointRepository
 *
 * @package Mautic\PointBundle\Entity
 */
class LeadPointLogRepository extends CommonRepository
{
    /**
     * Get a lead's point log
     *
     * @param integer $leadId
     * @param array   $ipIds
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadLogs($leadId, array $ipIds = array())
    {
        $query = $this->createQueryBuilder('el')
            ->select('IDENTITY(el.point) AS point_id, el.dateFired')
            ->where('el.lead = ' . $leadId);

        if (!empty($ipIds)) {
            $query->orWhere('el.ipAddress IN (' . implode(',', $ipIds) . ')');
        }

        return $query->getQuery()
            ->getArrayResult();
    }
}
