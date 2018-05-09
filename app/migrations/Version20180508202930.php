<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180508202930 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable($this->prefix.'emails')->hasColumn('use_owner_as_mailer')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE {$this->prefix}emails ADD use_owner_as_mailer TINYINT(4) AFTER email_type;");

        $ownerAsMailerConfigSetting = $this->container->getParameter('mautic.mailer_is_owner');

        $this->addSql("UPDATE {$this->prefix}emails SET use_owner_as_mailer = {$ownerAsMailerConfigSetting};");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE {$this->prefix}emails DROP use_owner_as_mailer;");
    }
}
