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
            ['p.dateModified', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'p';
    }

    public function getEntities(array $args = [])
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias())
            ->from('MauticProjectBundle:Project'/** @var MODEL */, $this->getTableAlias(), $this->getTableAlias().'.id')
            ->orderBy($this->getTableAlias().'.id');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }
}
