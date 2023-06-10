<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Helper;

use Doctrine\DBAL\Connection;

class CompanyHelper
{
    public function __construct(private Connection $connection)
    {
    }

    public function getCompanyName(int $id): string|bool
    {
        return $this->connection->createQueryBuilder()
            ->select('c.companyname')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'c')
            ->where('c.id = '.$id)
            ->execute()
            ->fetchOne();
    }
}
