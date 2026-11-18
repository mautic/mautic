<?php

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\ProjectBundle\Entity\ProjectRepositoryTrait;

/**
 * @extends CommonRepository<Point>
 */
class PointRepository extends CommonRepository
{
    use ProjectRepositoryTrait;

    public const SEARCH_COMMAND_TYPE_GAIN  = 'mautic.point.searchcommand.type_gain.filter';
    public const SEARCH_COMMAND_TYPE_LOSS  = 'mautic.point.searchcommand.type_loss.filter';
    public const SEARCH_COMMAND_REPEATABLE = 'mautic.point.searchcommand.isrepeatable';

    public function getEntities(array $args = [])
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias().', cat')
            ->from(Point::class, $this->getTableAlias())
            ->leftJoin($this->getTableAlias().'.category', 'cat')
            ->leftJoin($this->getTableAlias().'.group', 'pl');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    public function getTableAlias(): string
    {
        return 'p';
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
        $q = $this->createQueryBuilder('p')
            ->select('partial p.{id, type, name, delta, repeatable, properties}')
            ->setParameter('type', $type);

        // make sure the published up and down dates are good
        $expr = $this->getPublishedByDateOrmExpression($q);
        $expr->add($q->expr()->eq('p.type', ':type'));

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
            ->select('p.*')
            ->from(MAUTIC_TABLE_PREFIX.'point_lead_action_log', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX.'points', 'p', 'x.point_id = p.id');

        // make sure the published up and down dates are good
        $q->where(
            $q->expr()->and(
                $q->expr()->eq('p.type', ':type'),
                $q->expr()->eq('x.lead_id', (int) $leadId)
            )
        )
            ->setParameter('type', $type);

        $results = $q->executeQuery()->fetchAllAssociative();

        $return = [];

        foreach ($results as $r) {
            $return[$r['id']] = $r;
        }

        return $return;
    }

    /**
     * @param int $leadId
     */
    public function getCompletedLeadActionsByLeadId($leadId): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('p.*')
            ->from(MAUTIC_TABLE_PREFIX.'point_lead_action_log', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX.'points', 'p', 'x.point_id = p.id');

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
            'p.name',
            'p.description',
        ]);
    }

    protected function addSearchCommandWhereClause($q, $filter): array
    {
        $command             = $filter->command;
        $projectCommand      = $this->translator->trans('mautic.project.searchcommand.name');
        $projectCommandEn    = $this->translator->trans('mautic.project.searchcommand.name', [], null, 'en_US');
        $typeGainCommand     = $this->translator->trans(self::SEARCH_COMMAND_TYPE_GAIN);
        $typeGainCommandEn   = $this->translator->trans(self::SEARCH_COMMAND_TYPE_GAIN, [], null, 'en_US');
        $typeLossCommand     = $this->translator->trans(self::SEARCH_COMMAND_TYPE_LOSS);
        $typeLossCommandEn   = $this->translator->trans(self::SEARCH_COMMAND_TYPE_LOSS, [], null, 'en_US');

        if ($command === $projectCommand || $command === $projectCommandEn) {
            return $this->handleProjectFilter(
                $this->_em->getConnection()->createQueryBuilder(),
                'point_id',
                'point_projects_xref',
                $this->getTableAlias(),
                $filter->string,
                $filter->not
            );
        }

        $repeatableCommand   = $this->translator->trans(self::SEARCH_COMMAND_REPEATABLE);
        $repeatableCommandEn = $this->translator->trans(self::SEARCH_COMMAND_REPEATABLE, [], null, 'en_US');

        if ($command === $repeatableCommand || $command === $repeatableCommandEn) {
            return $this->addRepeatableWhereClause($q, $filter);
        }

        if ($command === $typeGainCommand || $command === $typeGainCommandEn) {
            return [$this->buildDeltaTypeExpression($q, true, $filter->not), []];
        }

        if ($command === $typeLossCommand || $command === $typeLossCommandEn) {
            return [$this->buildDeltaTypeExpression($q, false, $filter->not), []];
        }

        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge(
            [
                self::SEARCH_COMMAND_TYPE_GAIN,
                self::SEARCH_COMMAND_TYPE_LOSS,
                'mautic.project.searchcommand.name',
                self::SEARCH_COMMAND_REPEATABLE,
            ],
            $this->getStandardSearchCommands()
        );
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $q
     * @param object                                                       $filter
     *
     * @return array{0:mixed,1:array<string,bool>}
     */
    private function addRepeatableWhereClause($q, $filter): array
    {
        $column    = $this->getTableAlias().'.repeatable';
        $parameter = $this->generateRandomParameterName();

        $expr = $filter->not
            ? $q->expr()->neq($column, ":$parameter")
            : $q->expr()->eq($column, ":$parameter");

        return [
            $expr,
            [$parameter => true],
        ];
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $q
     */
    private function buildDeltaTypeExpression($q, bool $isGain, bool $not): mixed
    {
        $field = $this->getTableAlias().'.delta';

        if ($isGain) {
            return $not ? $q->expr()->lte($field, '0') : $q->expr()->gt($field, '0');
        }

        return $not ? $q->expr()->gte($field, '0') : $q->expr()->lt($field, '0');
    }
}
