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
 * Trackables
 */
class Version20160429000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'url_trackables')) {

            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {

        $sql = <<<SQL

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
CREATE TABLE mautic_url_trackables (redirect_id INT NOT NULL, source_id INT NOT NULL, source VARCHAR(255) NOT NULL, PRIMARY KEY(redirect_id, source_id));
CREATE INDEX IDX_AE477DCDB42D874D ON mautic_url_trackables (redirect_id);
ALTER TABLE mautic_url_trackables ADD CONSTRAINT FK_AE477DCDB42D874D FOREIGN KEY (redirect_id) REFERENCES mautic_page_redirects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

);
SQL;

        $this->addSql($sql);
    }

    /**
     * Migrate email redirects to the trackable table
     *
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $logger = $this->factory->getLogger();
        $qb = $this->connection->createQueryBuilder();

        $qb->select('r.id, r.email_id')
            ->from($this->prefix.'page_redirects', 'r')
            ->where(
                $qb->expr()->isNotNull('r.email_id')
            )
            ->setMaxResults(500);

        $start = 0;
        while ($results = $qb->execute()->fetchAll()) {
            // Start a transaction
            $this->connection->beginTransaction();

            foreach ($results as $row) {
                $insert = array(
                    'redirect_id' => $row['id'],
                    'channel'     => 'email',
                    'channel_id'  => $row['email_id']
                );

                $this->connection->insert($this->prefix.'channel_url_trackables', $insert);

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

        // Update all redirects with an email_id as trackable
        $qb = $this->connection->createQueryBuilder();
        $qb->update($this->prefix.'page_redirects')
            ->set($qb->expr()->eq('is_trackable', ':true'))
            ->setParameter('true', true, 'boolean')
            ->where($qb->expr()->isNotNull('email_id'))
            ->execute();

        // Update all redirects without an email_id as not trackable
        $qb = $this->connection->createQueryBuilder();
        $qb->update($this->prefix.'page_redirects')
            ->set($qb->expr()->eq('is_trackable', ':false'))
            ->setParameter('false', false, 'boolean')
            ->where($qb->expr()->isNull('email_id'))
            ->execute();


    }
}
