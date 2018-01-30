<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
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
use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * Universal DNC Migration.
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

        $this->leadIdIdx = $this->generatePropertyName($this->prefix.'lead_donotcontact', 'idx', ['lead_id']);
        $this->leadIdFk  = $this->generatePropertyName($this->prefix.'lead_donotcontact', 'fk', ['lead_id']);
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->prefix}lead_donotcontact` (
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
     * Migrate existing email_donotemail entries to the new lead_donotcontact format.
     *
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $logger = $this->factory->getLogger();
        $qb     = $this->connection->createQueryBuilder();

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

                $insert = [
                    'lead_id'    => $row['lead_id'],
                    'channel'    => 'email',
                    'channel_id' => $row['email_id'],
                    'date_added' => $row['date_added'],
                    'comments'   => $row['comments'],
                ];

                switch (true) {
                    case !empty($row['unsubscribed']):
                        $insert['reason'] = DoNotContact::UNSUBSCRIBED;
                        break;
                    case !empty($row['bounced']):
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

                $logger->addError($e->getMessage(), ['exception' => $e]);
            }

            // Increase the start
            $start += 500;
            $qb->setFirstResult($start);
        }
    }
}
