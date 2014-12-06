<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class TriggerRepository
 */
class TriggerRepository extends CommonRepository
{

    /**
     * {@inheritdoc}
     */
    public function getEntities($args = array())
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias() . ', cat')
            ->from('MauticPointBundle:Trigger', $this->getTableAlias())
            ->leftJoin($this->getTableAlias().'.category', 'cat');

        $this->buildClauses($q, $args);

        $query = $q->getQuery();

        if (isset($args['hydration_mode'])) {
            $mode = strtoupper($args['hydration_mode']);
            $query->setHydrationMode(constant("\\Doctrine\\ORM\\Query::$mode"));
        }

        $results = new Paginator($query);

        return $results;
    }

    /**
     * Get a list of published triggers with color and points
     *
     * @return array
     */
    public function getTriggerColors()
    {
        $now = new \DateTime();

        $q = $this->_em->createQueryBuilder()
            ->select('partial t.{id, color, points}')
            ->from('MauticPointBundle:Trigger', 't', 't.id');

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

        $q->orderBy('t.points', 'ASC');

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 't';
    }

    /**
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, array(
            't.name',
            't.description'
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }
}
