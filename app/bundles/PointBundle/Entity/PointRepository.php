<?php

namespace Mautic\PointBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\ProjectBundle\Entity\ProjectRepositoryTrait;

/**
 * @extends CommonRepository<Point>
 */
class PointRepository extends CommonRepository
{
    use ProjectRepositoryTrait;

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
        $typeCommand         = $this->translator->trans('mautic.point.searchcommand.type');
        $typeCommandEn       = $this->translator->trans('mautic.point.searchcommand.type', [], null, 'en_US');
        $gainValues          = [
            $this->translator->trans('mautic.point.searchcommand.type_gain'),
            $this->translator->trans('mautic.point.searchcommand.type_gain', [], null, 'en_US'),
        ];
        $lossValues          = [
            $this->translator->trans('mautic.point.searchcommand.type_loss'),
            $this->translator->trans('mautic.point.searchcommand.type_loss', [], null, 'en_US'),
        ];

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

        $repeatableCommand   = $this->translator->trans('mautic.point.searchcommand.isrepeatable');
        $repeatableCommandEn = $this->translator->trans('mautic.point.searchcommand.isrepeatable', [], null, 'en_US');

        if ($command === $repeatableCommand || $command === $repeatableCommandEn) {
            return $this->addRepeatableWhereClause($q, $filter);
        }

        $typeValue = null;
        if ($command === $typeCommand || $command === $typeCommandEn) {
            $typeValue = $filter->string;
        } elseif (str_contains($command, ':')) {
            [$baseCommand, $subCommand] = explode(':', $command, 2);
            if ($baseCommand === $typeCommand || $baseCommand === $typeCommandEn) {
                $typeValue = $subCommand;
            }
        }

        if (null !== $typeValue) {
            return [$this->buildDeltaTypeExpression($q, $typeValue, $filter->not, $gainValues, $lossValues), []];
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
                'mautic.point.searchcommand.type_gain.filter',
                'mautic.point.searchcommand.type_loss.filter',
                'mautic.project.searchcommand.name',
                'mautic.point.searchcommand.isrepeatable',
            ],
            $this->getStandardSearchCommands()
        );
    }

    /**
     * @param DbalQueryBuilder|QueryBuilder $q
     * @param object                        $filter
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
     * @param QueryBuilder|DbalQueryBuilder $q
     * @param string[]                      $gainValues
     * @param string[]                      $lossValues
     */
    private function buildDeltaTypeExpression($q, string $value, bool $not, array $gainValues, array $lossValues): mixed
    {
        $field = $this->getTableAlias().'.delta';

        if (in_array($value, $gainValues, true)) {
            return $not ? $q->expr()->lte($field, '0') : $q->expr()->gt($field, '0');
        }

        if (in_array($value, $lossValues, true)) {
            return $not ? $q->expr()->gte($field, '0') : $q->expr()->lt($field, '0');
        }

        return $q->expr()->eq(1, $not ? 1 : 0);
    }
}
