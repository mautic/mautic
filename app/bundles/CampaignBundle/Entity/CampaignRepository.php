<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CampaignRepository
 *
 * @package Mautic\CampaignBundle\Entity
 */
class CampaignRepository extends CommonRepository
{

    /**
     * Returns a list of all published (and active) campaigns (optionally for a specific lead)
     *
     * @param null $specificId
     * @param null $leadId
     * @param bool $forList If true, returns ID and name only
     *
     * @return array
     */
    public function getPublishedCampaigns($specificId = null, $leadId = null, $forList = false)
    {
        $q   = $this->_em->createQueryBuilder()
            ->from('MauticCampaignBundle:Campaign', 'c', 'c.id');

        $now = new \DateTime();
        if ($forList) {
            $q->select('c.id, c.name');
        } else {
            $q->select('c, l');
        }

        $q->leftJoin('c.leads', 'l')
            ->leftJoin('c.events', 'e')
            ->leftJoin('e.log', 'o')
            ->where($this->getPublishedByDateExpression($q))
            ->setParameter('now', $now);

        if (!empty($specificId)) {
            $q->andWhere(
                $q->expr()->eq('c.id', (int) $specificId)
            );
        }

        if (!empty($leadId)) {
            $q->andWhere(
                $q->expr()->eq('IDENTITY(l.lead)', (int) $leadId)
            );
        }

        $results = $q->getQuery()->getArrayResult();
        return $results;
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'c';
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, array(
            'c.name',
            'c.description'
        ));
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }
}
