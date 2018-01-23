<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use OpenCloud\Queues\Resource\Message;

/**
 * Class Version20151207000000.
 */
class Version20151207000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'email_copies')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS '.$this->prefix.'email_copies (id VARCHAR(32) NOT NULL, date_created DATETIME NOT NULL, body LONGTEXT DEFAULT NULL, subject LONGTEXT DEFAULT NULL, PRIMARY KEY(id))  DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_stats ADD copy_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_stats ADD CONSTRAINT '.$this->generatePropertyName('email_stats', 'fk', ['copy_id']).' FOREIGN KEY (copy_id) REFERENCES '.$this->prefix.'email_copies (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('email_stats', 'idx', ['copy_id']).' ON '.$this->prefix.'email_stats (copy_id)');

        $this->addSql('ALTER TABLE '.$this->prefix.'email_stats DROP FOREIGN KEY '.$this->findPropertyName('email_stats', 'fk', 'A832C1C9'));
        $this->addSql('ALTER TABLE '.$this->prefix.'email_stats ADD CONSTRAINT '.$this->generatePropertyName('email_stats', 'fk', ['email_id']).' FOREIGN KEY (email_id) REFERENCES '.$this->prefix.'emails (id) ON DELETE SET NULL');
    }

    /**
     * Inject notice into session regarding Telize.
     *
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        if ('telize' == $this->factory->getParameter('ip_lookup_service')) {
            $session = $this->container->get('session');
            $router  = $this->container->get('router');

            $configUrl = $router->generate('mautic_config_action', ['objectAction' => 'edit']);
            $message   = <<<MESSAGE
Recently Telize discontinued their free IP lookup service; continued use of this service now requires a subscription and an API key. Alternatively you can change the provider through the <a href="$configUrl" data-toggle="ajax">Configuration</a>: System Settings. ​<strong>MaxMind GeoIP2 City Download</strong>​ is the recommended alternative. This service uses a free IP database you can download within Mautic.
MESSAGE;

            $session->set('post_upgrade_message', $message);
        }
    }
}
