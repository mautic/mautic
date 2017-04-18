<?php

/**
 * @copyright   2017 Mautic Contributors. All rights reserved
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
use Mautic\EmailBundle\Model\EmailModel;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170401000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'email_stats');
        if ($table->hasColumn('is_clicked') === true) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}emails ADD clicked_count INT NOT NULL;");
        $this->addSql("ALTER TABLE {$this->prefix}email_stats ADD is_clicked TINYINT(1) NOT NULL, ADD date_clicked DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)';");
        $this->addSql("CREATE INDEX stat_email_clicked_search ON {$this->prefix}email_stats (is_clicked);");
    }

    /**
     * Update all the channel events with their assigned channels.
     *
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {

        /** @var EmailModel $emailModel */
        $emailModel = $this->container->get('mautic.email.model.email');
        $statRepo   = $emailModel->getStatRepository();

        $logger = $this->container->get('monolog.logger.mautic');

        /** @var \Doctrine\DBAL\Query\QueryBuilder $qb */
        $qb = $this->connection->createQueryBuilder();

        // get all email_stats where lead and email ids are not null and where there is a page hit
        $qb->select('es.email_id as emailId, es.id as statId, es.lead_id as leadId')
            ->addSelect('ph.date_hit as hit')
            ->from($this->prefix.'email_stats', 'es')
            ->join('es', 'page_hits', 'ph', 'ph.email_id = es.email_id')
            ->where('es.email_id IS NOT NULL')
            ->andWhere('es.lead_id IS NOT NULL')
            ->andWhere('ph.lead_id = es.lead_id');

        $results = $qb->execute();

        while (($res = $results->fetch()) !== false) {
            /** @var \Mautic\EmailBundle\Entity\Stat $stat */
            $stat    = $statRepo->find($res['statId']);
            $dateHit = $res['hit'];
            if ($stat->isClicked() === true) {
                // this Email is already counted as clicked
                $logger->debug('Email already clicked');
                continue;
            }

            $email = $stat->getEmail();
            $lead  = $stat->getLead();

            $emailModel->getRepository()->upCount($email->getId(), 'clicked', 1, $email->isVariant());

            $stat->setDateClicked($dateHit);
            $stat->setIsClicked(true);

            $statRepo->saveEntity($stat, true);

            $logger->debug('Email id: '.$email->getId().', lead_id : '.$lead->getId().' counted as clicked');
        }
    }
}
