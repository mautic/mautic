<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\GraphHelper;

/**
 * LeadRepository
 */
class LeadRepository extends CommonRepository
{
    /**
     * Get the details of leads added to a campaign
     *
     * @param      $campaignId
     * @param null $leads
     */
    public function getLeadDetails($campaignId, $leads = null)
    {
        $q = $this->_em->createQueryBuilder()
            ->from('MauticCampaignBundle:Lead', 'lc')
            ->select('lc')
            ->leftJoin('lc.campaign', 'c')
            ->leftJoin('lc.lead', 'l');
        $q->where(
            $q->expr()->eq('c.id', ':campaign')
        )->setParameter('campaign', $campaignId);

        if (!empty($leads)) {
            $q->andWhere(
                $q->expr()->in('l.id', ':leads')
            )->setParameter('leads', $leads);
        }

        $results = $q->getQuery()->getArrayResult();

        $return = array();
        foreach ($results as $r) {
            $return[$r['lead_id']][] = $r;
        }

        return $return;
    }

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
            ->from(MAUTIC_TABLE_PREFIX . 'campaign_leads', 'cl')
            ->leftJoin('cl', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'l.id = cl.lead_id')
            ->where(
                $dq->expr()->eq('cl.manually_removed', ':false')
            )
            ->setParameter('false', false, 'boolean');

        //Fix arguments if necessary
        $args = $this->convertOrmProperties('Mautic\\LeadBundle\\Entity\\Lead', $args);

        if (isset($args['campaign_id'])) {
            $dq->andWhere($dq->expr()->eq('cl.campaign_id', ':campaign'))
                ->setParameter('campaign', $args['campaign_id']);
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
            ->from(MAUTIC_TABLE_PREFIX . 'ip_addresses', 'i')
            ->leftJoin('i', MAUTIC_TABLE_PREFIX . 'lead_ips_xref', 'ix', 'i.id = ix.ip_id')
            ->where('l.id = ix.lead_id')
            ->orderBy('i.id', 'DESC');
        $ipQuery->setMaxResults(1);

        $dq->select('l.*, ' . sprintf('(%s)', $ipQuery->getSQL()) . ' as ip_address'); //single IP address

        $leads = $dq->execute()->fetchAll();

        return (!empty($args['withTotalCount'])) ?
            array(
                'count' => $total,
                'results' => $leads
            ) : $leads;
    }

    /**
     * Get leads for a specific campaign
     *
     * @param      $campaignId
     * @param null $eventId
     */
    public function getLeads($campaignId, $eventId = null)
    {
        $q = $this->_em->createQueryBuilder()
            ->from('MauticCampaignBundle:Lead', 'lc')
            ->select('lc, l')
            ->leftJoin('lc.campaign', 'c')
            ->leftJoin('lc.lead', 'l');
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('lc.manuallyRemoved', ':false'),
                $q->expr()->eq('c.id', ':campaign')
            )
        )
            ->setParameter('false', false, 'boolean')
            ->setParameter('campaign', $campaignId);

        if ($eventId != null) {
            $dq = $this->_em->createQueryBuilder();
            $dq->select('el.id')
                ->from('MauticCampaignBundle:LeadEventLog', 'ell')
                ->leftJoin('ell.lead', 'el')
                ->leftJoin('ell.event', 'ev')
                ->where(
                    $dq->expr()->eq('ev.id', ':eventId')
                );

            $q->andWhere('l.id NOT IN('.$dq->getDQL().')')
                ->setParameter('eventId', $eventId);
        }

        $result = $q->getQuery()->getResult();

        return $result;
    }

    /**
     * Fetch Lead stats for some period of time.
     *
     * @param integer $quantity of units
     * @param string $unit of time php.net/manual/en/class.dateinterval.php#dateinterval.props
     * @param array $options
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($quantity, $unit, $options = array())
    {
        $graphData = GraphHelper::prepareDatetimeLineGraphData($quantity, $unit, array('viewed'));

        // Load points for selected period
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('cl.date_added')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl');

        $utc = new \DateTimeZone('UTC');
        $graphData['fromDate']->setTimezone($utc);

        $q->andwhere(
            $q->expr()->andX(
                $q->expr()->gte('cl.date_added', ':date'),
                $q->expr()->eq('cl.manually_removed', ':false')
            )
        )
            ->setParameter('date', $graphData['fromDate']->format('Y-m-d H:i:s'))
            ->setParameter('false', false, 'boolean')
            ->orderBy('cl.date_added', 'ASC');

        if (isset($options['campaign_id'])) {
            $q->andwhere($q->expr()->gte('cl.campaign_id', (int) $options['campaign_id']));
        }

        $leads = $q->execute()->fetchAll();
        $total = false;

        if (isset($options['total']) && $options['total']) {
            // Count total until date
            $q->select('count(cl.lead_id) as total');

            $total = $q->execute()->fetchAll();
            $total = (int) $total[0]['total'];
        }

        return GraphHelper::mergeLineGraphData($graphData, $leads, $unit, 0, 'date_added', null, false, $total);
    }


    /**
     * Updates lead ID (e.g. after a lead merge)
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('cl.campaign_id')
            ->from(MAUTIC_TABLE_PREFIX . 'campaign_leads', 'cl')
            ->where('cl.lead_id = ' . $toLeadId)
            ->execute()
            ->fetchAll();
        $campaigns = array();
        foreach ($results as $r) {
            $campaigns[] = $r['campaign_id'];
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX . 'campaign_leads')
            ->set('lead_id', (int)$toLeadId)
            ->where('lead_id = ' . (int)$fromLeadId);

        if (!empty($campaigns)) {
            $q->andWhere(
                $q->expr()->notIn('campaign_id', $campaigns)
            )->execute();

            // Delete remaining leads as the new lead already belongs
            $this->_em->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX . 'campaign_leads')
                ->where('lead_id = ' . (int)$fromLeadId)
                ->execute();
        } else {
            $q->execute();
        }
    }
}
