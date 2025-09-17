<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

final class ProjectRepository extends CommonRepository
{
    /**
     * @var array<string, string>
     */
    private const ASSOCIATION_XREF_TABLES = [
        'mautic.project.searchcommand.hasemails'    => 'email_projects_xref',
        'mautic.project.searchcommand.hascampaigns' => 'campaign_projects_xref',
        'mautic.project.searchcommand.haspages'     => 'page_projects_xref',
        'mautic.project.searchcommand.hasassets'    => 'asset_projects_xref',
    ];

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        [$expr, $parameters] = parent::addSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        foreach (self::ASSOCIATION_XREF_TABLES as $translationKey => $xrefTable) {
            $command   = $this->translator->trans($translationKey);
            $commandEn = $this->translator->trans($translationKey, [], null, 'en_US');

            if ($filter->command === $command || $filter->command === $commandEn) {
                return $this->buildAssociationExpression($xrefTable, (bool) $filter->not);
            }
        }

        return [$expr, $parameters];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildAssociationExpression(string $xrefTable, bool $negation): array
    {
        $queryBuilder = $this->_em->getConnection()->createQueryBuilder();
        $ids          = $queryBuilder
            ->select('DISTINCT project_id')
            ->from(MAUTIC_TABLE_PREFIX.$xrefTable, 'projectxref')
            ->executeQuery()
            ->fetchFirstColumn();

        $ids = array_map('intval', $ids ?: []);

        if ([] === $ids) {
            $ids = [0];
        }

        $field = sprintf('%s.id', $this->getTableAlias());
        $expr  = $negation
            ? $queryBuilder->expr()->notIn($field, $ids)
            : $queryBuilder->expr()->in($field, $ids);

        return [$expr, []];
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge(array_keys(self::ASSOCIATION_XREF_TABLES), parent::getSearchCommands());
    }

    /**
     * @return array<string[]>
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['p.dateModified', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'p';
    }

    public function checkProjectNameExists(string $name, ?int $ignoredId = null): bool
    {
        $q = $this->createQueryBuilder($this->getTableAlias());
        $q->select('1');
        $q->where($this->getTableAlias().'.name = :name');
        $q->setParameter('name', $name);
        $q->setMaxResults(1);

        if (null !== $ignoredId) {
            $q->andWhere($q->expr()->neq($this->getTableAlias().'.id', ':ignoredId'));
            $q->setParameter('ignoredId', $ignoredId);
        }

        return !empty($q->getQuery()->getResult());
    }
}
