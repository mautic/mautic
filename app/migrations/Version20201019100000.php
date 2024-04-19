<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201019100000 extends AbstractMauticMigration
{
    protected static string $tableName = 'emails_draft';

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->hasTable($this->getPrefixedTableName())) {
            throw new SkipMigration(sprintf('Table %s already exists. Skipping migration', $this->getPrefixedTableName()));
        }
    }

    public function up(Schema $schema): void
    {
        $emailsTable = $schema->getTable($this->getPrefixedTableName('emails'));
        $idColumn    = $emailsTable->getColumn('id');
        $idDataType  = 'SIGNED';
        $fkName      = $this->generatePropertyName('emails_draft', 'fk', ['email_id']);
        $ukName      = $this->generatePropertyName('emails_draft', 'uniq', ['email_id']);

        if (true === $idColumn->getUnsigned()) {
            $idDataType = 'UNSIGNED';
        }
        $this->addSql(
            sprintf(
                'CREATE TABLE `%s` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `email_id` int(10) %s NOT NULL,
                      `html` longtext,
                      `template` varchar(191) DEFAULT NULL,
                      `public_preview` tinyint(1) DEFAULT 1 NOT NULL,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `%s` (`email_id`),
                      CONSTRAINT `%s` FOREIGN KEY (`email_id`) REFERENCES `%s` (`id`)
                    )DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;',
                $this->getPrefixedTableName(),
                $idDataType,
                $ukName,
                $fkName,
                $this->getPrefixedTableName('emails')
            )
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName());
    }
}
