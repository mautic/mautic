<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\LeadBundle\Entity\ListLead;

/**
 * Schema update for Version 1.0.0-rc2 to 1.0.0-rc3
 *
 * Class Version20150307000000
 *
 * @package Mautic\Migrations
 */
class Version20150307000000 extends AbstractMauticMigration
{
    private $includedLeads;
    private $excludedLeads;

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema)
    {
        // Test to see if this migration has already been applied
        if ($schema->hasTable($this->prefix . 'lead_lists_leads')) {
            throw new SkipMigrationException('Schema includes this migration');
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')->from($this->prefix . 'lead_lists_included_leads', 'il');
        $this->includedLeads = $qb->execute()->fetchAll();

        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')->from($this->prefix . 'lead_lists_excluded_leads', 'el');
        $this->excludedLeads = $qb->execute()->fetchAll();
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        // Rebuild the lists

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $this->factory->getModel('lead.list');

        $lists = $listModel->getEntities(array('ignore_paginator' => true));

        foreach ($lists as $l) {
            $listModel->rebuildListLeads($l);
        }

        $leads = $listModel->getLeadsByList($lists, true);

        $persist = array();
        $em = $this->factory->getEntityManager();

        foreach ($this->includedLeads as $l) {
            if (!in_array($l['lead_id'], $leads[$l['leadlist_id']])) {
                $listLead = new ListLead();
                $listLead->setList($lists[$l['leadlist_id']]);
                $listLead->setLead($em->getReference('MauticLeadBundle:Lead', $l['lead_id']));
                $listLead->setManuallyAdded(true);
                $listLead->setDateAdded(new \DateTime());

                $lists[$l['leadlist_id']]->addLead($l['lead_id'], $listLead);
                $persist[$l['leadlist_id']] = $lists[$l['leadlist_id']];
            }
        }

        $listLeadRepository = $listModel->getListLeadRepository();
        foreach ($this->excludedLeads as $l) {
            if (in_array($l['lead_id'], $leads[$l['leadlist_id']])) {
                $listLead = $listLeadRepository->findOneBy(array(
                    'lead' => $l['lead_id'],
                    'list' => $l['leadlist_id']
                ));

                $listLead->setManuallyRemoved(true);
                $lists[$l['leadlist_id']]->addLead($l['lead_id'], $listLead);
                $persist[$l['leadlist_id']] = $lists[$l['leadlist_id']];
            }
        }

        $listLeadRepository->saveEntities($persist);
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $this->addSql('CREATE TABLE ' . $this->prefix . 'lead_lists_leads (leadlist_id INT NOT NULL, lead_id INT NOT NULL, date_added DATETIME NOT NULL, manually_removed TINYINT(1) NOT NULL, manually_added TINYINT(1) NOT NULL, INDEX ' . $this->generatePropertyName('lead_lists_leads', 'idx', array('leadlist_id')) . ' (leadlist_id), INDEX ' . $this->generatePropertyName('lead_lists_leads', 'idx', array('lead_id')) . ' (lead_id), PRIMARY KEY(leadlist_id, lead_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_lists_leads ADD CONSTRAINT ' . $this->generatePropertyName('lead_lists_leads', 'fk', array('leadlist_id')) . ' FOREIGN KEY (leadlist_id) REFERENCES ' . $this->prefix . 'lead_lists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_lists_leads ADD CONSTRAINT ' . $this->generatePropertyName('lead_lists_leads', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE ' . $this->prefix . 'lead_lists_excluded_leads');
        $this->addSql('DROP TABLE ' . $this->prefix . 'lead_lists_included_leads');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets ADD extension VARCHAR(255) DEFAULT NULL, ADD mime VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql('CREATE TABLE ' . $this->prefix . 'lead_lists_leads (leadlist_id INT NOT NULL, lead_id INT NOT NULL, date_added TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, manually_removed BOOLEAN NOT NULL, manually_added BOOLEAN NOT NULL, PRIMARY KEY(leadlist_id, lead_id))');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('lead_lists_leads', 'idx', array('leadlist_id')). ' ON ' . $this->prefix . 'lead_lists_leads (leadlist_id)');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('lead_lists_leads', 'idx', array('lead_id')) . ' ON ' . $this->prefix . 'lead_lists_leads (lead_id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_lists_leads ADD CONSTRAINT ' . $this->generatePropertyName('lead_lists_leads', 'fk', array('leadlist_id')) . ' FOREIGN KEY (leadlist_id) REFERENCES ' . $this->prefix . 'lead_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_lists_leads ADD CONSTRAINT ' . $this->generatePropertyName('lead_lists_leads', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE ' . $this->prefix . 'lead_lists_included_leads');
        $this->addSql('DROP TABLE ' . $this->prefix . 'lead_lists_excluded_leads');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets ADD extension VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets ADD mime VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function mssqlUp(Schema $schema)
    {
        $this->addSql('CREATE TABLE ' . $this->prefix . 'lead_lists_leads (leadlist_id INT NOT NULL, lead_id INT NOT NULL, date_added DATETIME2(6) NOT NULL, manually_removed BIT NOT NULL, manually_added BIT NOT NULL, PRIMARY KEY (leadlist_id, lead_id))');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('lead_lists_leads', 'idx', array('leadlist_id')) . ' ON ' . $this->prefix . 'lead_lists_leads (leadlist_id)');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('lead_lists_leads', 'idx', array('lead_id')) . ' ON ' . $this->prefix . 'lead_lists_leads (lead_id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_lists_leads ADD CONSTRAINT ' . $this->generatePropertyName('lead_lists_leads', 'fk', array('leadlist_id')) . ' FOREIGN KEY (leadlist_id) REFERENCES ' . $this->prefix . 'lead_lists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_lists_leads ADD CONSTRAINT ' . $this->generatePropertyName('lead_lists_leads', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE ' . $this->prefix . 'lead_lists_excluded_leads');
        $this->addSql('DROP TABLE ' . $this->prefix . 'lead_lists_included_leads');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets ADD extension NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets ADD mime NVARCHAR(255)');
    }
}