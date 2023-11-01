<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Helper;

use Doctrine\DBAL\Connection;

class CompanyHelper
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return string|bool
     */
    public function getCompanyName(int $id)
    {
        return $this->connection->createQueryBuilder()
            ->select('c.companyname')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'c')
            ->where('c.id = '.$id)
            ->execute()
            ->fetchColumn();
    }
}
