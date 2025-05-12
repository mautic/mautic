<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\PageBundle\Entity\PageDraft;

final class Version20201029001005 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->hasTable($this->getPrefixedTableName(PageDraft::TABLE_NAME))) {
            throw new SkipMigration(sprintf('Table %s already exists. Skipping migration', $this->getPrefixedTableName(PageDraft::TABLE_NAME)));
        }
    }

    public function up(Schema $schema): void
    {
        $idDataType = $this->getColumnTypeSignedOrUnsigned($schema, 'pages', 'id');
        $fkName     = $this->generatePropertyName('pages_draft', 'fk', ['page_id']);
        $ukName     = $this->generatePropertyName('pages_draft', 'uniq', ['page_id']);

        $this->addSql(
            sprintf(
                'CREATE TABLE `%s` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `page_id` int(10) %s NOT NULL,
                  `html` longtext,
                  `template` varchar(191) DEFAULT NULL,
                  `public_preview` tinyint(1) NOT NULL DEFAULT 1,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `%s` (`page_id`),
                  CONSTRAINT `%s` FOREIGN KEY (`page_id`) REFERENCES `%s` (`id`)
                )DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;',
                $this->getPrefixedTableName(PageDraft::TABLE_NAME),
                $idDataType,
                $ukName,
                $fkName,
                $this->getPrefixedTableName('pages')
            )
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName(PageDraft::TABLE_NAME));
    }
}
