<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230606111852 extends PreUpAssertionMigration
{
    public const OLD_STRING = 'Connect a &quot;Send Email&quot; action to the top of this decision.';
    public const NEW_STRING = 'Connect a Send Email action to the top of this decision.';

    protected static $tableName = 'campaign_events';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            $sql         = sprintf("select id from %s where properties like '%s' limit 1", $this->getPrefixedTableName(self::$tableName), '%'.self::OLD_STRING.'%');
            $recordCount = $this->connection->executeQuery($sql)->fetchAllAssociative();

            return !$recordCount;
        }, 'Migration is not required.');
    }

    public function up(Schema $schema): void
    {
        $sql            = sprintf("select id, properties from %s where properties like '%s'", $this->getPrefixedTableName(self::$tableName), '%'.self::OLD_STRING.'%');
        $results        = $this->connection->executeQuery($sql)->fetchAllAssociative();
        $updatedRecords = 0;
        foreach ($results as $row) {
            $propertiesArray                            = unserialize($row['properties']);
            $propertiesArray['settings']['description'] = str_replace(self::OLD_STRING, self::NEW_STRING, $propertiesArray['settings']['description']);
            $propertiesString                           = serialize($propertiesArray);

            $sql  = sprintf('UPDATE %s SET properties = :properties where id = :id', $this->getPrefixedTableName(self::$tableName));
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam('properties', $propertiesString, \PDO::PARAM_STR);
            $stmt->bindParam('id', $row['id'], \PDO::PARAM_INT);
            $stmt->executeStatement();

            $updatedRecords += $stmt->rowCount();
        }
        $this->write(sprintf('<comment>%s record(s) have been updated successfully.</comment>', $updatedRecords));
    }
}
