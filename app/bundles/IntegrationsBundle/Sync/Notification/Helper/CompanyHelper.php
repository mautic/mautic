<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
