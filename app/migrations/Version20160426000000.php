<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * Universal DNC Migration
 */
class Version20160426000000 extends AbstractMauticMigration
{
    private $leadIdIdx;
    private $leadIdFk;

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'lead_donotcontact')) {
            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->leadIdIdx = $this->generatePropertyName($this->prefix . 'lead_donotcontact', 'idx', array('lead_id'));
        $this->leadIdFk  = $this->generatePropertyName($this->prefix . 'lead_donotcontact', 'fk', array('lead_id'));
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {

        $sql = <<<SQL
CREATE TABLE `{$this->prefix}lead_donotcontact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `channel` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `reason` smallint NOT NULL,
  `comments` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `{$this->leadIdIdx}` (`lead_id`),
  KEY `{$this->prefix}dnc_reason_search` (`reason`),
  CONSTRAINT `{$this->leadIdFk}` FOREIGN KEY (`lead_id`) REFERENCES `{$this->prefix}leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql("CREATE SEQUENCE {$this->prefix}lead_donotcontact_id_seq INCREMENT BY 1 MINVALUE 1 START 1");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}lead_donotcontact (
  id INT NOT NULL, 
  lead_id INT DEFAULT NULL, 
  date_added TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
  reason SMALLINT NOT NULL, 
  channel VARCHAR(255) NOT NULL, 
  channel_id INT DEFAULT NULL, 
  comments TEXT DEFAULT NULL, 
  PRIMARY KEY(id)
);
SQL;

        $this->addSql($sql);

        $this->addSql("CREATE INDEX {$this->leadIdIdx} ON {$this->prefix}lead_donotcontact (lead_id)");
        $this->addSql("CREATE INDEX {$this->prefix}dnc_reason_search ON {$this->prefix}lead_donotcontact (reason)");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}lead_donotcontact.date_added IS '(DC2Type:datetime)'");
        $this->addSql("ALTER TABLE {$this->prefix}lead_donotcontact ADD CONSTRAINT {$this->leadIdFk} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * Migrate existing email_donotemail entries to the new lead_donotcontact format
     *
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $logger = $this->factory->getLogger();
        $qb = $this->connection->createQueryBuilder();

        $qb->select('dne.email_id, dne.lead_id, dne.date_added, dne.unsubscribed, dne.bounced, dne.manual, dne.comments')
            ->from($this->prefix.'email_donotemail', 'dne')
            ->setMaxResults(500);

        $start = 0;
        while ($results = $qb->execute()->fetchAll()) {
            // Start a transaction
            $this->connection->beginTransaction();

            foreach ($results as $row) {
                // Build the new format
                if (empty($row['lead_id'])) {
                    continue;
                }

                $insert = array(
                    'lead_id'    => $row['lead_id'],
                    'channel'    => 'email',
                    'channel_id' => $row['email_id'],
                    'date_added' => $row['date_added'],
                    'comments'   => $row['comments']
                );

                if ('postgresql' == $this->platform) {
                    // Get ID from sequence
                    $nextVal = (int)$this->connection->fetchColumn(
                        $this->connection->getDatabasePlatform()->getSequenceNextValSQL("{$this->prefix}lead_donotcontact_id_seq")
                    );
                    $insert['id'] = $nextVal;
                }

                switch (true) {
                    case (!empty($row['unsubscribed'])):
                        $insert['reason'] = DoNotContact::UNSUBSCRIBED;
                        break;
                    case (!empty($row['bounced'])):
                        $insert['reason'] = DoNotContact::BOUNCED;
                        break;
                    default:
                        $insert['reason'] = DoNotContact::MANUAL;
                        break;
                }

                $this->connection->insert($this->prefix.'lead_donotcontact', $insert);

                unset($insert);
            }

            try {
                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();

                $logger->addError($e->getMessage(), array('exception' => $e));
            }

            // Increase the start
            $start += 500;
            $qb->setFirstResult($start);
        }
    }
}
