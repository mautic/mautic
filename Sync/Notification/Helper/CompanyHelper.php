<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Notification\Helper;


use Doctrine\DBAL\Connection;

class CompanyHelper
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * CompanyHelper constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function getCompanyName(int $id): string
    {
        return $this->connection->createQueryBuilder()
            ->select('c.companyname')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'c')
            ->where('c.id = '.$id)
            ->execute()
            ->fetchColumn();
    }
}