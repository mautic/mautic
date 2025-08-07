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
}
