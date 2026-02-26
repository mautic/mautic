<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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

    public function getProjectByName(string $name, ?int $ignoredId = null): ?Project
    {
        $connection   = $this->getEntityManager()->getConnection();
        $isPostgreSql = $connection->getDatabasePlatform() instanceof PostgreSQLPlatform;
        $where        = ($isPostgreSql ?
            'LOWER('.$this->getTableAlias().'.name) = LOWER(:name)' :
            $this->getTableAlias().'.name = :name'
        );

        $q = $this->createQueryBuilder($this->getTableAlias());
        $q->where($where);
        $q->setParameter('name', $name);

        if (null !== $ignoredId) {
            $q->andWhere($q->expr()->neq($this->getTableAlias().'.id', ':ignoredId'));
            $q->setParameter('ignoredId', $ignoredId);
        }

        return $q->getQuery()->getOneOrNullResult();
    }

    public function checkProjectNameExists(string $name, ?int $ignoredId = null): bool
    {
        return !empty($this->getProjectByName($name, $ignoredId));
    }
}
