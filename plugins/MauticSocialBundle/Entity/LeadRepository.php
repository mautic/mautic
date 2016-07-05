<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * LeadRepository
 *
 */
class LeadRepository extends CommonRepository
{
    /**
     * Get leads for a specific campaign
     *
     * @param      $campaignId
     * @param null $eventId
     */
    public function getLeadsWithFields($args)
    {
        //DBAL
        $dq = $this->_em->getConnection()->createQueryBuilder();
        $dq->select('count(cl.lead_id) as count')
            ->from(MAUTIC_TABLE_PREFIX.'monitoring_leads', 'cl')
            ->leftJoin('cl', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = cl.lead_id');

        //Fix arguments if necessary
        $args = $this->convertOrmProperties('Mautic\\LeadBundle\\Entity\\Lead', $args);

        if (isset($args['monitor_id'])) {
            $dq->andWhere($dq->expr()->eq('cl.monitor_id', ':monitor'))
                ->setParameter('monitor', $args['monitor_id']);
        }

        //get a total count
        $result = $dq->execute()->fetchAll();
        $total  = $result[0]['count'];

        //now get the actual paginated results
        $this->buildOrderByClause($dq, $args);
        $this->buildLimiterClauses($dq, $args);

        $dq->resetQueryPart('select');

        $ipQuery = $this->_em->getConnection()->createQueryBuilder();
        $ipQuery->select('i.ip_address')
            ->from(MAUTIC_TABLE_PREFIX.'ip_addresses', 'i')
            ->leftJoin('i', MAUTIC_TABLE_PREFIX.'lead_ips_xref', 'ix', 'i.id = ix.ip_id')
            ->where('l.id = ix.lead_id')
            ->orderBy('i.id', 'DESC');
        $ipQuery->setMaxResults(1);

        $dq->select('l.*, '.sprintf('(%s)', $ipQuery->getSQL()).' as ip_address'); //single IP address

        $leads = $dq->execute()->fetchAll();

        return (!empty($args['withTotalCount'])) ?
            [
                'count'   => $total,
                'results' => $leads
            ] : $leads;
    }
}

