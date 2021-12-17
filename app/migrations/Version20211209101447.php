<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20211209101447 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $tableName      = $this->prefix.'lead_lists';
        $sql            = sprintf(
            "select id, filters from %s where filters like '%s' or filters like '%s'",
            $tableName,
            '%dnc_unsubscribed_manually%',
            '%dnc_unsubscribed_sms_manually%'
        );
        $results        = $this->connection->executeQuery($sql)->fetchAll();
        $updatedRecords = 0;
        foreach ($results as $row) {
            $filters = unserialize($row['filters']);

            $serializedFilters = serialize(array_map(function ($filter) {
                if ('dnc_unsubscribed_manually' === $filter['field']) {
                    $filter['field'] = 'dnc_manual_email';
                } elseif ('dnc_unsubscribed_sms_manually' === $filter['field']) {
                    $filter['field'] = 'dnc_manual_sms';
                }

                return $filter;
            }, $filters));

            $sql  = sprintf('UPDATE %s SET filters = :filters where id = :id', $tableName);
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam('filters', $serializedFilters, \PDO::PARAM_STR);
            $stmt->bindParam('id', $row['id'], \PDO::PARAM_INT);
            $stmt->execute();

            $updatedRecords += $stmt->rowCount();
        }
        $this->write(sprintf('<comment>%s record(s) have been updated successfully.</comment>', $updatedRecords));
    }
}
