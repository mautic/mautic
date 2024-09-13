<?php

namespace Mautic\StageBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Stage>
 */
class StageRepository extends CommonRepository
{
    public function getEntities(array $args = [])
    {
        $q = $this
            ->createQueryBuilder($this->getTableAlias())
            ->leftJoin($this->getTableAlias().'.category', 'c');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    public function getTableAlias(): string
    {
        return 's';
    }

    /**
     * Get array of published actions based on type.
     *
     * @param string $type
     *
     * @return array
     */
    public function getPublishedByType($type)
    {
        $q = $this->createQueryBuilder('s')
            ->select('partial s.{id, name}')
            ->setParameter('type', $type);

        // make sure the published up and down dates are good
        $expr = $this->getPublishedByDateExpression($q);

        $q->where($expr);

        return $q->getQuery()->getResult();
    }

    /**
     * @param string $type
     * @param int    $leadId
     */
    public function getCompletedLeadActions($type, $leadId): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('s.*')
            ->from(MAUTIC_TABLE_PREFIX.'stage_lead_action_log', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX.'stages', 's', 'x.stage_id = s.id');

        // make sure the published up and down dates are good
        $q->where(
            $q->expr()->and(
                $q->expr()->eq('x.lead_id', (int) $leadId)
            )
        );

        $results = $q->executeQuery()->fetchAllAssociative();

        $return = [];

        foreach ($results as $r) {
            $return[$r['id']] = $r;
        }

        return $return;
    }

    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            's.name',
            's.description',
        ]);
    }

    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * Get a list of lists.
     *
     * @param bool   $user
     * @param string $id
     *
     * @return array
     */
    public function getStages($user = false, $id = '')
    {
        static $stages = [];

        if (is_object($user)) {
            $user = $user->getId();
        }

        $key = (int) $user.$id;
        if (isset($stages[$key])) {
            return $stages[$key];
        }

        $q = $this->_em->createQueryBuilder()
            ->from(Stage::class, 's', 's.id');

        $q->select('partial s.{id, name}')
            ->andWhere($q->expr()->eq('s.isPublished', ':true'))
            ->setParameter('true', true, 'boolean');

        if (!empty($user)) {
            $q->orWhere('s.createdBy = :user');
            $q->setParameter('user', $user);
        }

        if (!empty($id)) {
            $q->andWhere(
                $q->expr()->neq('s.id', $id)
            );
        }

        $q->orderBy('s.name');

        $results = $q->getQuery()->getArrayResult();

        $stages[$key] = $results;

        return $results;
    }

    /**
     * Get a list of stages.
     *
     * @return array
     */
    public function getStageByName($stageName)
    {
        if (!$stageName) {
            return false;
        }

        $q = $this->_em->createQueryBuilder()
            ->from(Stage::class, 's', 's.id');

        $q->select('partial s.{id, name}')
            ->andWhere($q->expr()->eq('s.isPublished', ':true'))
            ->setParameter('true', true, 'boolean');
        $q->andWhere('s.name = :stage')
            ->setParameter('stage', $stageName);

        $result = $q->getQuery()->getResult();

        if ($result) {
            $key = array_keys($result);

            return $result[$key[0]];
        }

        return null;
    }

    /**
     * @param string|int $value
     *
     * @return array
     */
    public function findByIdOrName($value)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('s')
            ->from(Stage::class, 's');

        if (is_numeric($value)) {
            // This is numeric value so check id and name
            $qb->where('s.id = :value');
        } else {
            // This is string, no need to check IDs
            $qb->where('s.name = :value');
        }

        return $qb
            ->setParameter('value', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
