<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230606111852 extends PreUpAssertionMigration
{
    public const OLD_STRING = 'Connect a &quot;Send Email&quot; action to the top of this decision.';

    public const NEW_STRING = 'Connect a Send Email action to the top of this decision.';

    protected const TABLE_NAME = 'campaign_events';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            $column = $this->connection->quoteIdentifier('properties');
            $like   = DatabasePlatform::getCaseInsensitiveLike(
                $this->connection->getDatabasePlatform(),
                $column,
                ':pattern'
            );
            $sql    = sprintf('select id from %s where %s limit 1', $this->getPrefixedTableName(), $like);
            $recordCount = $this->connection->executeQuery(
                $sql,
                ['pattern' => '%'.self::OLD_STRING.'%']
            )->fetchAllAssociative();

            return !$recordCount;
        }, 'Migration is not required.');
    }

    public function up(Schema $schema): void
    {
        $column         = $this->connection->quoteIdentifier('properties');
        $like           = DatabasePlatform::getCaseInsensitiveLike(
            $this->connection->getDatabasePlatform(),
            $column,
            ':pattern'
        );
        $sql            = sprintf('select id, properties from %s where %s', $this->getPrefixedTableName(), $like);
        $results        = $this->connection->executeQuery(
            $sql,
            ['pattern' => '%'.self::OLD_STRING.'%']
        )->fetchAllAssociative();
        $updatedRecords = 0;
        foreach ($results as $row) {
            $propertiesArray                            = unserialize($row['properties']);
            $propertiesArray['settings']['description'] = str_replace(self::OLD_STRING, self::NEW_STRING, $propertiesArray['settings']['description']);
            $propertiesString                           = serialize($propertiesArray);

            $sql  = sprintf('UPDATE %s SET properties = :properties where id = :id', $this->getPrefixedTableName());
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue('properties', $propertiesString, \PDO::PARAM_STR);
            $stmt->bindValue('id', $row['id'], \PDO::PARAM_INT);
            $updatedRecords += $stmt->executeStatement();
        }
        $this->write(sprintf('<comment>%s record(s) have been updated successfully.</comment>', $updatedRecords));
    }
}
