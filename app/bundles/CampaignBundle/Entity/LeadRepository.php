<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
        //Get the list of custom fields
        $fq = $this->_em->getConnection()->createQueryBuilder();
        $fq->select('f.id, f.label, f.alias, f.type, f.field_group as `group`')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_fields', 'f')
            ->where('f.is_published = 1');
        $results = $fq->execute()->fetchAll();

        $fields = array();
        foreach ($results as $r) {
            $fields[$r['alias']] = $r;
        }

        //DBAL
        $dq = $this->_em->getConnection()->createQueryBuilder();
        $dq->select('count(*) as count')
            ->from(MAUTIC_TABLE_PREFIX . 'campaign_leads', 'cl')
            ->leftJoin('cl', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'l.id = cl.lead_id');

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
        $dq->select('l.*');
        $leads = $dq->execute()->fetchAll();

        //loop over results to put fields in something that can be assigned to the entities
        $fieldValues = array();
        $leadEntities = array();
        foreach ($leads as $key => $lead) {
            $entity = $this->createFromArray('\Mautic\LeadBundle\Entity\Lead', $lead);

            $leadEntities[$entity->getId()] = $entity;

            $leadId = $entity->getId();

            //whatever is left over is a custom field
            foreach ($lead as $k => $r) {
                if (isset($fields[$k])) {
                    $fieldValues[$leadId][$fields[$k]['group']][$fields[$k]['alias']] = $fields[$k];
                    $fieldValues[$leadId][$fields[$k]['group']][$fields[$k]['alias']]['value'] = $r;
                    unset($lead[$k]);
                }
            }

            $entity->setFields($fieldValues[$leadId]);
        }

        return (!empty($args['withTotalCount'])) ?
            array(
                'count' => $total,
                'results' => $leadEntities
            ) : $leadEntities;
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
            $q->expr()->eq('c.id', ':campaign')
        )->setParameter('campaign', $campaignId);

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
     * COunt leads for a specific campaign
     *
     * @param      $campaignId
     * @return int
     */
    public function countLeads($campaignId)
    {
        $q = $this->createQueryBuilder('cl')
            ->select('count(cl.lead) as theCount');
        $q->where(
            $q->expr()->eq('cl.campaign', ':campaign')
        )->setParameter('campaign', $campaignId);

        $result = $q->getQuery()->getSingleResult();

        return (int) $result['theCount'];
    }

    /**
     * Fetch Lead stats for some period of time.
     * 
     * @param integer $quantity of units
     * @param string $unit of time php.net/manual/en/class.dateinterval.php#dateinterval.props
     * @param array $args
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($quantity, $unit, $args = array())
    {
        $graphData = GraphHelper::prepareLineGraphData($quantity, $unit, array('viewed'));

        // Load points for selected period
        $q = $this->createQueryBuilder('cl');
        $q->select('cl.dateAdded');

        $q->andwhere($q->expr()->gte('cl.dateAdded', ':date'))
            ->setParameter('date', $graphData['fromDate'])
            ->orderBy('cl.dateAdded', 'ASC');

        $leads = $q->getQuery()->getArrayResult();

        // Count total until date
        $q2 = $this->createQueryBuilder('cl');
        $q2->select('count(cl.lead) as total');
        
        $q2->andwhere($q->expr()->lt('cl.dateAdded', ':date'))
            ->setParameter('date', $graphData['fromDate']);

        $total = $q2->getQuery()->getSingleResult();
        $total = (int) $total['total'];

        return GraphHelper::mergeLineGraphData($graphData, $leads, $unit, 0, 'dateAdded', null, false, $total);
    }
}