<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @extends CommonRepository<CompanySegment>
 */
class CompanySegmentRepository extends CommonRepository
{
    /**
     * The original method name is `getLists`.
     *
     * @see LeadListRepository::getLists
     *
     * @return array<array{id: string, name: string, alias: string}>
     */
    public function getSegments(?User $user = null, ?string $alias = null, ?int $id = null): array
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from(CompanySegment::class, $this->getTableAlias(), $this->getTableAlias().'.id');

        $q->select('partial '.$this->getTableAlias().'.{id, name, alias}')
            ->andWhere($q->expr()->eq($this->getTableAlias().'.isPublished', ':true'))
            ->setParameter('true', true, 'boolean');

        if (null !== $user) {
            $q->andWhere(
                $q->expr()->orX(
                    $q->expr()->eq($this->getTableAlias().'.createdBy', ':user')
                )
            );
            $q->setParameter('user', $user->getId());
        }

        if (null !== $alias && '' !== $alias) {
            $q->andWhere($this->getTableAlias().'.alias = :alias');
            $q->setParameter('alias', $alias);
        }

        if (null !== $id && $id > 0) {
            $q->andWhere(
                $q->expr()->neq($this->getTableAlias().'.id', $id)
            );
        }

        $q->orderBy($this->getTableAlias().'.name');

        /** @var array<array{id: string, name: string, alias: string}> $result */
        $result = $q->getQuery()->getArrayResult();

        return $result;
    }

    public function getTableAlias(): string
    {
        return CompanySegment::DEFAULT_ALIAS;
    }

    /**
     * The override as in \Mautic\LeadBundle\Entity\LeadListRepository::addSearchCommandWhereClause.
     * Stripped conditions for search.
     *
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @phpstan-param mixed $filter
     *
     * @return array<mixed>
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        \assert($filter instanceof \stdClass);
        [$expr, $parameters] = parent::addStandardSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = false;

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.name'):
            case $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US'):
                $expr            = $q->expr()->like($this->getTableAlias().'.name', ':'.$unique);
                $returnParameter = true;
                break;
        }

        if ($returnParameter) {
            /** @phpstan-ignore-next-line */
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = [$unique => $string];
        }

        return [
            $expr,
            $parameters,
        ];
    }

    /**
     * @return string[]|array<string, string[]>
     */
    public function getSearchCommands(): array
    {
        $commands = [
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.name',
            'mautic.core.searchcommand.category',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * This function is required for both generating reports by company segment and for the LeuchtfeuerCompanyListWidgetBundle.
     *
     * @param array<int> $ids
     *
     * @return array<int, CompanySegment>
     */
    public function getSegmentObjectsViaListOfIDs(array $ids = []): array
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from(CompanySegment::class, CompanySegment::DEFAULT_ALIAS, CompanySegment::DEFAULT_ALIAS.'.id');

        $q->select('cs')
            ->andWhere($q->expr()->eq(CompanySegment::DEFAULT_ALIAS.'.isPublished', ':true'))
            ->setParameter('true', true, 'boolean');

        if ([] !== $ids) {
            $q->andWhere($q->expr()->in(CompanySegment::DEFAULT_ALIAS.'.id', $ids));
        }

        $return = $q->getQuery()->getResult();

        if (!is_array($return)) {
            throw new UnexpectedTypeException($return, 'array');
        }

        foreach ($return as $index => $item) {
            if (!is_int($index)) {
                throw new UnexpectedTypeException($index, 'int');
            }

            if (!$item instanceof CompanySegment) {
                throw new UnexpectedTypeException($index, CompanySegment::class);
            }
        }

        /** @var array<int, CompanySegment> $return */
        return $return;
    }
}
