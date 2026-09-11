<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Entity\CommonRepository;

final class ProjectRepository extends CommonRepository
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
        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();

        $where = DatabasePlatform::getCaseInsensitiveLike(
            $platform,
            $this->getTableAlias().'.name',
            ':name',
            DatabasePlatform::FLAG_FORCE_LOWER_COLUMN | DatabasePlatform::FLAG_FORCE_LOWER_VALUE
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
        return $this->getProjectByName($name, $ignoredId) instanceof Project;
    }
}
