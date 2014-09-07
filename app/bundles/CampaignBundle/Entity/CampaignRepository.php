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
     * Get a list of published campaigns with color and campaigns
     *
     * @return array
     */
    public function getCampaignColors()
    {
        $now = new \DateTime();

        $q = $this->_em->createQueryBuilder()
            ->select('partial t.{id, color, campaigns}')
            ->from('MauticCampaignBundle:Campaign', 't', 't.id');

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('t.isPublished', true),
                $q->expr()->orX(
                    $q->expr()->isNull('t.publishUp'),
                    $q->expr()->gte('t.publishUp', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('t.publishDown'),
                    $q->expr()->lte('t.publishDown', ':now')
                )
            )
        )
            ->setParameter('now', $now);

        $q->orderBy('t.campaigns', 'ASC');

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    public function getTableAlias()
    {
        return 't';
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, array(
            't.name',
            't.description'
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
