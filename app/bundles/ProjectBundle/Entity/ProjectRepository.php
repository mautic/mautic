<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;

final class ProjectRepository extends CommonRepository
{
    /**
     * @var array<string, array{xrefTable: string, entityClass: class-string, entityTable?: string, xrefEntityColumn?: string}>
     */
    private const ASSOCIATION_FILTERS = [
        'mautic.project.searchcommand.hasemails'    => ['entityClass' => Email::class, 'xrefTable' => 'email_projects_xref'],
        'mautic.project.searchcommand.hascampaigns' => ['entityClass' => Campaign::class, 'xrefTable' => 'campaign_projects_xref', 'entityTable' => Campaign::TABLE_NAME, 'xrefEntityColumn' => 'campaign_id'],
        'mautic.project.searchcommand.haspages'      => ['entityClass' => Page::class, 'xrefTable' => 'page_projects_xref'],
        'mautic.project.searchcommand.hasassets'     => ['entityClass' => Asset::class, 'xrefTable' => 'asset_projects_xref'],
    ];

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     *
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        [$expr, $parameters] = parent::addSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        foreach (self::ASSOCIATION_FILTERS as $translationKey => $association) {
            $command   = $this->translator->trans($translationKey);
            $commandEn = $this->translator->trans($translationKey, [], null, 'en_US');

            if ($filter->command === $command || $filter->command === $commandEn) {
                return $this->buildAssociationExpression($q, $association, (bool) $filter->not);
            }
        }

        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @param array{xrefTable: string, entityClass: class-string, entityTable?: string, xrefEntityColumn?: string} $association
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildAssociationExpression(QueryBuilder|DbalQueryBuilder $q, array $association, bool $negation): array
    {
        $field = sprintf('%s.id', $this->getTableAlias());

        if ($q instanceof QueryBuilder) {
            $subQuery = $this->_em->createQueryBuilder()
                ->select('associated.id')
                ->from($association['entityClass'], 'associated')
                ->innerJoin('associated.projects', 'associatedProject')
                ->where('associatedProject.id = '.$field);

            if (isset($association['entityTable'])) {
                $subQuery->andWhere('associated.deleted IS NULL');
            }

            $expr = $q->expr()->exists($subQuery->getDQL());
            if ($negation) {
                $expr = $q->expr()->not($expr);
            }

            return [(string) $expr, []];
        }

        $subQuery = $this->_em->getConnection()->createQueryBuilder()
            ->select('projectxref.project_id')
            ->from(MAUTIC_TABLE_PREFIX.$association['xrefTable'], 'projectxref');

        if (isset($association['entityTable'], $association['xrefEntityColumn'])) {
            $subQuery
                ->innerJoin('projectxref', MAUTIC_TABLE_PREFIX.$association['entityTable'], 'associated', sprintf('associated.id = projectxref.%s', $association['xrefEntityColumn']))
                ->where('associated.deleted IS NULL');
        }

        $expr = $negation
            ? $q->expr()->notIn($field, $subQuery->getSQL())
            : $q->expr()->in($field, $subQuery->getSQL());

        return [$expr, []];
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge(
            array_keys(self::ASSOCIATION_FILTERS),
            ['mautic.core.searchcommand.ismine'],
            parent::getSearchCommands()
        );
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
