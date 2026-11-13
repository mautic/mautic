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
     * @var array<string, array{xrefTable: string, entityClass: class-string}>
     */
    private const ASSOCIATION_FILTERS = [
        'mautic.project.searchcommand.hasemails'    => ['entityClass' => Email::class, 'xrefTable' => 'email_projects_xref'],
        'mautic.project.searchcommand.hascampaigns' => ['entityClass' => Campaign::class, 'xrefTable' => 'campaign_projects_xref'],
        'mautic.project.searchcommand.haspages'      => ['entityClass' => Page::class, 'xrefTable' => 'page_projects_xref'],
        'mautic.project.searchcommand.hasassets'     => ['entityClass' => Asset::class, 'xrefTable' => 'asset_projects_xref'],
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

        foreach (self::ASSOCIATION_FILTERS as $translationKey => $association) {
            $command   = $this->translator->trans($translationKey);
            $commandEn = $this->translator->trans($translationKey, [], null, 'en_US');

            if ($filter->command === $command || $filter->command === $commandEn) {
                return $this->buildAssociationExpression(
                    $q,
                    $association['xrefTable'],
                    $association['entityClass'],
                    (bool) $filter->not
                );
            }
        }

        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildAssociationExpression(QueryBuilder|DbalQueryBuilder $q, string $xrefTable, string $entityClass, bool $negation): array
    {
        $field = sprintf('%s.id', $this->getTableAlias());

        if ($q instanceof QueryBuilder) {
            $subQuery = $this->_em->createQueryBuilder()
                ->select('associated.id')
                ->from($entityClass, 'associated')
                ->innerJoin('associated.projects', 'associatedProject')
                ->where('associatedProject.id = '.$field);

            $expr = $q->expr()->exists($subQuery->getDQL());
            if ($negation) {
                $expr = $q->expr()->not($expr);
            }

            return [(string) $expr, []];
        }

        $subQuery = $this->_em->getConnection()->createQueryBuilder()
            ->select('projectxref.project_id')
            ->from(MAUTIC_TABLE_PREFIX.$xrefTable, 'projectxref');

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
