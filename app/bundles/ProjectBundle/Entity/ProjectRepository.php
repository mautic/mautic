<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class ProjectRepository extends CommonRepository
{
    /**
     * @return array<string[]>
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['p.date_modified', 'ASC'],
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
