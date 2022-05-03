<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Helper;

use Doctrine\DBAL\Connection;

class UserHelper
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getAdminUsers(): array
    {
        $qb      = $this->connection->createQueryBuilder();
        $results = $qb->select('u.id')
            ->from(MAUTIC_TABLE_PREFIX.'users', 'u')
            ->join('u', MAUTIC_TABLE_PREFIX.'roles', 'r', 'r.id = u.role_id')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('r.is_published', 1),
                    $qb->expr()->eq('r.is_admin', 1),
                    $qb->expr()->eq('u.is_published', 1)
                )
            )
            ->execute()->fetchAll();

        $admins = [];
        foreach ($results as $result) {
            $admins[] = (int) $result['id'];
        }

        return $admins;
    }
}
