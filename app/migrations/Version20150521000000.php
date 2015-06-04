<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PointBundle\Entity\Point;

/**
 * Migrate 1.0.5 to 1.0.6
 *
 * Class Version20150521000000
 */
class Version20150521000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        // Test to see if this migration has already been applied
        $table = $schema->getTable($this->prefix . 'form_fields');
        if ($table->hasColumn('lead_field')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        // Set save_result to true for most form fields
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'form_fields')
            ->set('save_result', ':true')
            ->where(
                $q->expr()->andX(
                    $q->expr()->neq('type', $q->expr()->literal('button')),
                    $q->expr()->neq('type', $q->expr()->literal('freetext')),
                    $q->expr()->neq('type', $q->expr()->literal('captcha'))
                )
            )
            ->setParameter('true', true, 'boolean')
            ->execute();

        // Find and migrate the lead.create actions

        // Get a list of lead field entities
        $results = $this->connection->createQueryBuilder()
            ->select('f.id, f.alias')
            ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
            ->execute()
            ->fetchAll();
        $fields  = array();
        foreach ($results as $field) {
            $fields[$field['id']] = $field['alias'];
        }
        unset($results);

        // Get all the actions that are lead.create
        $q       = $this->connection->createQueryBuilder();
        $actions = $q->select('a.id, a.properties, a.form_id, f.created_by, f.created_by_user')
            ->from(MAUTIC_TABLE_PREFIX.'form_actions', 'a')
            ->join('a', MAUTIC_TABLE_PREFIX.'forms', 'f', 'a.form_id = f.id')
            ->where(
                $q->expr()->eq('a.type', $q->expr()->literal('lead.create'))
            )
            ->execute()
            ->fetchAll();

        $formFieldMatches = array();
        $pointEntities    = array();
        $deleteActions    = array();
        foreach ($actions as $action) {
            try {
                $properties = unserialize($action['properties']);

                foreach ($properties['mappedFields'] as $leadFieldId => $formFieldId) {
                    if (!empty($formFieldId)) {
                        $formFieldMatches[$leadFieldId][] = $formFieldId;
                    }
                }

                if (!empty($properties['points'])) {
                    // Create a new point action
                    $point = new Point();
                    $point->setName('Migrated: Form #'.$action['form_id']);
                    $point->setDescription('<p>Migrated during 1.0.6 upgrade. The Create/Update Lead form submit action is now obsolete.</p>');
                    $point->setDelta($properties['points']);
                    $point->setCreatedBy($action['created_by']);
                    $point->setCreatedByUser($action['created_by_user']);
                    $point->setType('form.submit');
                    $point->setProperties(
                        array(
                            'forms' => array($action['form_id'])
                        )
                    );
                    $pointEntities[] = $point;
                    unset($point);
                }

                $deleteActions[] = $action['id'];

            } catch (\Exception $e) {

            }
        }

        foreach ($formFieldMatches as $leadFieldId => $formFieldIds) {
            if (!isset($fields[$leadFieldId])) {
                continue;
            }

            $q = $this->connection->createQueryBuilder();
            $q->update(MAUTIC_TABLE_PREFIX.'form_fields')
                ->set('lead_field', $q->expr()->literal($fields[$leadFieldId]))
                ->where(
                    $q->expr()->in('id', $formFieldIds)
                )
                ->execute();
        }

        if (!empty($pointEntities)) {
            $this->factory->getModel('point')->getRepository()->saveEntities($pointEntities);
        }

        if (!empty($deleteActions)) {
            $q = $this->connection->createQueryBuilder();
            $q->delete(MAUTIC_TABLE_PREFIX.'form_actions')
                ->where(
                    $q->expr()->in('id', $deleteActions)
                )
                ->execute();
        }

        // Set captcha form fields to required
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'form_fields')
            ->set('is_required', ':true')
            ->where(
                $q->expr()->eq('type', $q->expr()->literal('captcha'))
            )
            ->setParameter('true', true, 'boolean')
            ->execute();

        // Set all forms as standalone
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'forms')
            ->set('form_type', $q->expr()->literal('standalone'))
            ->execute();

        // Rebuild all the forms
        /** @var \Mautic\FormBundle\Model\FormModel $formModel */
        $formModel = $this->factory->getModel('form');
        $formRepo  = $formModel->getRepository();

        $q = $formRepo->createQueryBuilder('f')
            ->select('f, ff')
            ->leftJoin('f.fields', 'ff');

        $forms = $q->getQuery()->getResult();
        if (!empty($forms)) {
            foreach ($forms as $form) {
                // Rebuild the forms
                $formModel->generateHtml($form, false);
            }

            $formRepo->saveEntities($forms);

            $this->factory->getEntityManager()->clear('MauticFormBundle:Form');
        }

        // Clear template for custom mode
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'pages')
            ->set('template', $q->expr()->literal(''))
            ->where(
                $q->expr()->eq('content_mode', $q->expr()->literal('custom'))
            )
            ->execute();

        // Convert email landing page hits to redirects
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'page_hits')
            ->set('email_id', 'source_id')
            ->where(
                $q->expr()->eq('source', $q->expr()->literal('email')),
                $q->expr()->isNull('email_id')
            )
            ->execute();

        $q = $this->connection->createQueryBuilder();
        $clicks = $q->select('ph.url, ph.email_id, count(distinct(ph.tracking_id)) as unique_click_count, count(ph.tracking_id) as click_count')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'ph')
            ->where(
                $q->expr()->andX(
                    $q->expr()->isNotNull('email_id'),
                    $q->expr()->isNotNull('page_id')
                )
            )
            ->groupBy('ph.url, ph.email_id')
            ->execute()->fetchAll();

        // See which already have URLs created as redirects
        $redirectEntities = array();
        $em = $this->factory->getEntityManager();
        foreach ($clicks as $click) {
            $redirect = new Redirect();
            $redirect->setDateAdded(new \DateTime());
            $redirect->setEmail($em->getReference('MauticEmailBundle:Email', $click['email_id']));
            $redirect->setUrl($click['url']);
            $redirect->setHits($click['click_count']);
            $redirect->setUniqueHits($click['unique_click_count']);
            $redirect->setRedirectId();
            $redirectEntities[] = $redirect;
        }

        if (!empty($redirectEntities)) {
            $this->factory->getModel('page.redirect')->getRepository()->saveEntities($redirectEntities);
            $em->clear('MauticPageBundle:Redirect');
        }

        // Copy subjects as names
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'emails')
            ->set('name', 'subject')
            ->execute();

        // Clear template for custom mode
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'emails')
            ->set('template', $q->expr()->literal(''))
            ->where(
                $q->expr()->eq('content_mode', $q->expr()->literal('custom'))
            )
            ->execute();

        // Assume all as templates to start
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'emails')
            ->set('email_type', $q->expr()->literal('template'))
            ->execute();

        // Get a list of emails that have been sent to lead lists
        $q = $this->connection->createQueryBuilder();
        $q->select('s.email_id, count(*) as sent_count, sum(case when s.is_read then 1 else 0 end) as read_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->where(
                $q->expr()->isNotNull('s.list_id')
            )
            ->groupBy('s.email_id');

        $results = $q->execute()->fetchAll();

        if (!empty($results)) {
            $templateEmails   = array();
            foreach ($results as $email) {
                $templateEmails[$email['email_id']] = $email;
            }
            $templateEmailIds = array_keys($templateEmails);
            /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
            $emailModel = $this->factory->getModel('email');

            $emails = $emailModel->getEntities(
                array(
                    'iterator_mode' => true,
                    'filter'        => array(
                        'force' => array(
                            array(
                                'column' => 'e.id',
                                'expr'   => 'in',
                                'value'  => $templateEmailIds
                            )
                        )
                    )
                )
            );

            $persistListEmails = $persistTemplateEmails = $variants = array();

            // Clone since the ID may be in a bunch of serialized properties then convert new to a list based email
            while (($row = $emails->next()) !== false) {

                /** @var \Mautic\EmailBundle\Entity\Email $templateEmail */
                $templateEmail = reset($row);
                $id            = $templateEmail->getId();
                $listEmail     = clone($templateEmail);
                $listEmail->setEmailType('list');
                $listEmail->clearVariants();
                $listEmail->clearStats();

                $listSentCount = $templateEmails[$id]['sent_count'];
                $listReadCount = $templateEmails[$id]['read_count'];

                $currentSentCount = $templateEmail->getSentCount();
                $currentReadCount = $templateEmail->getReadCount();

                // Assume the difference between the current counts and the list counts are template related
                $templateEmail->setSentCount($currentSentCount - $listSentCount);
                $templateEmail->setReadCount($currentReadCount - $listReadCount);

                // Set the list email stats
                $listEmail->setSentCount($listSentCount);
                $listEmail->setReadCount($listReadCount);

                // Special cases for variants
                if ($variantStartDate = $templateEmail->getVariantStartDate()) {
                    // Take note that this email needs to also have it's variants
                    if (!in_array($id, $variants)) {
                        $variants[] = $id;
                    }

                    $dtHelper = new DateTimeHelper($variantStartDate);

                    $q = $this->connection->createQueryBuilder();
                    $q->select('s.email_id, count(*) as sent_count, sum(case when s.is_read then 1 else 0 end) as read_count')
                        ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
                        ->where(
                            $q->expr()->andX(
                                $q->expr()->isNotNull('s.list_id'),
                                $q->expr()->eq('s.email_id', $id),
                                $q->expr()->gte('s.date_sent', $q->expr()->literal($dtHelper->toUtcString('Y-m-d H:i:s')))
                            )
                        )
                        ->groupBy('s.email_id');

                    $results = $q->execute()->fetchAll();

                    $variantListSentCount = $results[0]['sent_count'];
                    $variantListReadCount = $results[0]['read_count'];

                    $variantCurrentSentCount = $templateEmail->getVariantSentCount();
                    $variantCurrentReadCount = $templateEmail->getVariantReadCount();

                    // Assume the difference between the current counts and the list counts are template related
                    $templateEmail->setVariantSentCount($variantCurrentSentCount - $variantListSentCount);
                    $templateEmail->setVariantReadCount($variantCurrentReadCount - $variantListReadCount);

                    // Set the list email stats
                    $listEmail->setVariantSentCount($variantListSentCount);
                    $listEmail->setVariantReadCount($variantListReadCount);
                }

                $persistListEmails[$id]     = $listEmail;
                $persistTemplateEmails[$id] = $templateEmail;

                unset($listEmail, $templateEmail);
            }

            $repo = $emailModel->getRepository();

            // Update template emails; no need to run through audit log stuff so just use repo
            $repo->saveEntities($persistTemplateEmails);

            // Create new list emails and tell audit log to use system
            define('MAUTIC_IGNORE_AUDITLOG_USER', 1);
            $emailModel->saveEntities($persistListEmails);

            // Clone variants
            $persistVariants   = array();
            $processedVariants = array();

            foreach ($variants as $templateEmailId) {
                if ($persistTemplateEmails[$templateEmailId]->isVariant(true)) {
                    // A variant of another so get parent
                    $templateParent = $persistTemplateEmails[$templateEmailId]->getVariantParent();
                } else {
                    $templateParent = $persistTemplateEmails[$templateEmailId];
                }

                if(in_array($templateParent->getId(), $processedVariants)) {
                    continue;
                }

                $processedVariants[] = $templateParent->getId();

                // Get the children to clone each one
                $children = $templateParent->getVariantChildren();

                // If the parent is not already cloned, then do so
                /** @var \Mautic\EmailBundle\Entity\Email $listParent */
                if (!isset($persistListEmails[$templateParent->getId()])) {
                    $listParent   = clone($templateParent);
                    $listParent->setEmailType('list');
                    $listParent->clearVariants();
                    $listParent->clearStats();

                    $persistVariants[$templateParent->getId()] = $listParent;
                } else {
                    $listParent = $persistListEmails[$templateParent->getId()];
                }

                unset($templateParent);

                /** @var \Mautic\EmailBundle\Entity\Email $templateChild */
                foreach ($children as $templateChild) {
                    // If the variant already exists, then just set the parent and save
                    if (isset($persistListEmails[$templateChild->getId()])) {
                        $persistListEmails[$templateChild->getId()]->setVariantParent($listParent);
                        $persistVariants[$templateChild->getId()] = $persistListEmails[$templateChild->getId()];

                        continue;
                    }

                    $listChild = clone($templateChild);
                    $listChild->clearStats();
                    $listChild->setEmailType('list');

                    $listChild->setVariantParent($listParent);

                    $persistVariants[$templateChild->getId()] = $listChild;

                    unset($listChild, $templateChild);
                }

                unset($listParent, $children);
            }

            // Create new variants
            $emailModel->saveEntities($persistVariants);

            // Now update lead log stats, page hit stats, and email stats
            foreach ($persistListEmails as $templateId => $listEmail) {
                // Update page hits
                $sq = $this->connection->createQueryBuilder();
                $sq->select('es.lead_id')
                    ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
                    ->where(
                        $sq->expr()->andX(
                            $sq->expr()->eq('es.email_id', $templateId),
                            $q->expr()->isNotNull('es.list_id')
                        )
                    );
                $q = $this->connection->createQueryBuilder();
                $q->update(MAUTIC_TABLE_PREFIX.'page_hits', 'ph')
                    ->set('ph.email_id', $listEmail->getId())
                    ->where('ph.lead_id IN ' . sprintf('(%s)', $sq->getSql()) . ' AND ph.email_id = ' . $templateId)
                    ->execute();

                // Update download hits
                $q = $this->connection->createQueryBuilder();
                $q->update(MAUTIC_TABLE_PREFIX.'asset_downloads', 'ad')
                    ->set('ad.email_id', $listEmail->getId())
                    ->where('ad.lead_id IN ' . sprintf('(%s)', $sq->getSql()) . ' AND ad.email_id = ' . $templateId)
                    ->execute();

                $q = $this->connection->createQueryBuilder();
                $q->update(MAUTIC_TABLE_PREFIX.'email_stats')
                    ->set('email_id', $listEmail->getId())
                    ->where(
                        $q->expr()->andX(
                            $q->expr()->isNotNull('list_id'),
                            $q->expr()->eq('email_id', $templateId)
                        )
                    )
                    ->execute();

                unset($listEmail, $persistListEmails[$templateId]);
            }

            // Delete all lead list cross references for the emails converted to templates
            $q = $this->connection->createQueryBuilder();
            $q->delete(MAUTIC_TABLE_PREFIX.'email_list_xref')
                ->where(
                    $q->expr()->in('email_id', $templateEmailIds)
                )
                ->execute();
        } else {
            // Delete all lead list cross references
            $q = $this->connection->createQueryBuilder();
            $q->delete(MAUTIC_TABLE_PREFIX.'email_list_xref')
                ->execute();
        }
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {

        $this->addSql(
            'CREATE TABLE ' . $this->prefix . 'email_assets_xref (email_id INT NOT NULL, asset_id INT NOT NULL, INDEX ' . $this->generatePropertyName('email_assets_xref', 'idx', array('email_id')) . '  (email_id), INDEX ' . $this->generatePropertyName('email_assets_xref', 'idx', array('asset_id')) . '  (asset_id), PRIMARY KEY(email_id, asset_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_assets_xref ADD CONSTRAINT ' . $this->generatePropertyName('email_assets_xref', 'fk', array('email_id')) . '  FOREIGN KEY (email_id) REFERENCES ' . $this->prefix . 'emails (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_assets_xref ADD CONSTRAINT ' . $this->generatePropertyName('email_assets_xref', 'fk', array('asset_id')) . '  FOREIGN KEY (asset_id) REFERENCES ' . $this->prefix . 'assets (id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets ADD size INT DEFAULT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log DROP FOREIGN KEY ' . $this->findPropertyName('campaign_lead_event_log', 'fk', 'F639F774'));
        $this->addSql(
            'ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('campaign_lead_event_log', 'fk', array('campaign_id')) . '  FOREIGN KEY (campaign_id) REFERENCES ' . $this->prefix . 'campaigns (id)'
        );

        $this->addSql(
            'ALTER TABLE ' . $this->prefix . 'emails ADD name VARCHAR(255) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD from_address VARCHAR(255) DEFAULT NULL, ADD from_name VARCHAR(255) DEFAULT NULL, ADD reply_to_address VARCHAR(255) DEFAULT NULL, ADD bcc_address VARCHAR(255) DEFAULT NULL, ADD email_type VARCHAR(255) DEFAULT NULL, CHANGE subject subject LONGTEXT DEFAULT NULL, CHANGE template template VARCHAR(255) DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE ' . $this->prefix . 'email_stats ADD copy LONGTEXT DEFAULT NULL, ADD open_count INT DEFAULT NULL, ADD last_opened DATETIME DEFAULT NULL, ADD open_details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\''
        );

        $this->addSql(
            'ALTER TABLE ' . $this->prefix . 'form_fields ADD container_attr VARCHAR(255) DEFAULT NULL, ADD lead_field VARCHAR(255) DEFAULT NULL, ADD save_result TINYINT(1) DEFAULT NULL, CHANGE `label` `label` LONGTEXT NOT NULL, CHANGE default_value default_value LONGTEXT DEFAULT NULL, CHANGE validation_message validation_message LONGTEXT DEFAULT NULL, CHANGE help_message help_message LONGTEXT DEFAULT NULL'
        );

        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages CHANGE template template VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'page_redirects ADD email_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE ' . $this->prefix . 'page_redirects ADD CONSTRAINT ' . $this->generatePropertyName('page_redirects', 'fk', array('email_id')) . ' FOREIGN KEY (email_id) REFERENCES ' . $this->prefix . 'emails (id) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('page_redirects', 'idx', array('email_id')) . ' ON ' . $this->prefix  . 'page_redirects (email_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'forms ADD form_type VARCHAR(255) DEFAULT NULL');

        $this->addSql('CREATE TABLE ' . $this->prefix . 'campaign_form_xref (campaign_id INT NOT NULL, form_id INT NOT NULL, INDEX ' . $this->generatePropertyName('campaign_form_xref', 'idx', array('campaign_id')) . ' (campaign_id), INDEX ' . $this->generatePropertyName('campaign_form_xref', 'idx', array('form_id')) . ' (form_id), PRIMARY KEY(campaign_id, form_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_form_xref ADD CONSTRAINT ' . $this->generatePropertyName('campaign_form_xref', 'fk', array('campaign_id')) . ' FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_form_xref ADD CONSTRAINT ' . $this->generatePropertyName('campaign_form_xref', 'fk', array('form_id')) . ' FOREIGN KEY (form_id) REFERENCES forms (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql('CREATE TABLE ' . $this->prefix . 'email_assets_xref (email_id INT NOT NULL, asset_id INT NOT NULL, PRIMARY KEY(email_id, asset_id))');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('email_assets_xref', 'idx', array('email_id')) . '  ON ' . $this->prefix . 'email_assets_xref (email_id)');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('email_assets_xref', 'idx', array('asset_id')) . '  ON ' . $this->prefix . 'email_assets_xref (asset_id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_assets_xref ADD CONSTRAINT ' . $this->generatePropertyName('email_assets_xref', 'fk', array('email_id')) . '  FOREIGN KEY (email_id) REFERENCES ' . $this->prefix . 'emails (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_assets_xref ADD CONSTRAINT ' . $this->generatePropertyName('email_assets_xref', 'fk', array('asset_id')) . '  FOREIGN KEY (asset_id) REFERENCES ' . $this->prefix . 'assets (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets ADD size INT DEFAULT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log DROP CONSTRAINT ' . $this->findPropertyName('campaign_lead_event_log', 'fk', 'F639F774'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log ADD CONSTRAINT ' . $this->generatePropertyName('campaign_lead_event_log', 'fk', array('campaign_id')) . '  FOREIGN KEY (campaign_id) REFERENCES ' . $this->prefix . 'campaigns (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD from_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD from_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD reply_to_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD bcc_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD email_type VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ALTER subject TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ALTER subject DROP NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ALTER template DROP NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ADD copy TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ADD open_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ADD last_opened TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ADD open_details TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN ' . $this->prefix . 'email_stats.open_details IS \'(DC2Type:array)\'');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD container_attr VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD lead_field VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD save_result BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER label TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER default_value TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER validation_message TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER help_message TYPE TEXT');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages ALTER template DROP NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'page_redirects ADD email_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'page_redirects ADD CONSTRAINT ' . $this->generatePropertyName('page_redirects', 'fk', array('email_id')) . ' FOREIGN KEY (email_id) REFERENCES ' . $this->prefix . 'emails (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('page_redirects', 'idx', array('email_id')) . ' ON ' . $this->prefix  . 'page_redirects (email_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'forms ADD form_type VARCHAR(255) DEFAULT NULL');

        $this->addSql('CREATE TABLE ' . $this->prefix . 'campaign_form_xref (campaign_id INT NOT NULL, form_id INT NOT NULL, PRIMARY KEY(campaign_id, form_id))');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('campaign_form_xref', 'idx', array('campaign_id') . '  ON ' . $this->prefix . 'campaign_form_xref (campaign_id)'));
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('campaign_form_xref', 'idx', array('form_id') . '  ON ' . $this->prefix . 'campaign_form_xref (form_id)'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_form_xref ADD CONSTRAINT ' . $this->generatePropertyName('campaign_form_xref', 'fk', array('campaign_id')) . '  FOREIGN KEY (campaign_id) REFERENCES ' . $this->prefix . 'campaigns (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_form_xref ADD CONSTRAINT ' . $this->generatePropertyName('campaign_form_xref', 'fk', array('form_id')) . '  FOREIGN KEY (form_id) REFERENCES ' . $this->prefix . 'forms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     */
    public function mssqlUp(Schema $schema)
    {
        $this->addSql('CREATE TABLE ' . $this->prefix . 'email_assets_xref (email_id INT NOT NULL, asset_id INT NOT NULL, PRIMARY KEY (email_id, asset_id))');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('email_assets_xref', 'idx', array('email_id')) . '  ON ' . $this->prefix . 'email_assets_xref (email_id)');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('email_assets_xref', 'idx', array('asset_id')) . '  ON ' . $this->prefix . 'email_assets_xref (asset_id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_assets_xref ADD CONSTRAINT ' . $this->generatePropertyName('email_assets_xref', 'fk', array('email_id')) . '  FOREIGN KEY (email_id) REFERENCES ' . $this->prefix . 'emails (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_assets_xref ADD CONSTRAINT ' . $this->generatePropertyName('email_assets_xref', 'fk', array('asset_id')) . '  FOREIGN KEY (asset_id) REFERENCES ' . $this->prefix . 'assets (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets ADD size INT');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD name NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD description VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD from_address NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD from_name NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD reply_to_address NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD bcc_address NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD email_type NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ALTER COLUMN subject VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ALTER COLUMN template NVARCHAR(255)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ADD copy VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ADD open_count INT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ADD last_opened DATETIME2(6)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ADD open_details VARCHAR(MAX)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD container_attr NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD lead_field NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD save_result BIT');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages ALTER COLUMN template NVARCHAR(255)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'page_redirects ADD email_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'page_redirects ADD CONSTRAINT ' . $this->generatePropertyName('page_redirects', 'fk', array('email_id')) . ' FOREIGN KEY (email_id) REFERENCES ' . $this->prefix . 'emails (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('page_redirects', 'idx', array('email_id')) . ' ON ' . $this->prefix  . 'page_redirects (email_id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'forms ADD form_type NVARCHAR(255)');

        $this->addSql('CREATE TABLE ' . $this->prefix . 'campaign_form_xref (campaign_id INT NOT NULL, form_id INT NOT NULL, PRIMARY KEY (campaign_id, form_id))');
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('campaign_form_xref', 'idx', array('campaign_id') . '  ON ' . $this->prefix . 'campaign_form_xref (campaign_id)'));
        $this->addSql('CREATE INDEX ' . $this->generatePropertyName('campaign_form_xref', 'idx', array('form_id') . '  ON ' . $this->prefix . 'campaign_form_xref (form_id)'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_form_xref ADD CONSTRAINT ' . $this->generatePropertyName('campaign_form_xref', 'fk', array('campaign_id')) . '  FOREIGN KEY (campaign_id) REFERENCES ' . $this->prefix . 'campaigns (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_form_xref ADD CONSTRAINT ' . $this->generatePropertyName('campaign_form_xref', 'fk', array('form_id')) . '  FOREIGN KEY (form_id) REFERENCES ' . $this->prefix . 'forms (id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'addon_integration_settings ALTER COLUMN supported_features VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'addon_integration_settings ALTER COLUMN api_keys VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'addon_integration_settings ALTER COLUMN feature_settings VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth2_clients ALTER COLUMN redirect_uris VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'oauth2_clients ALTER COLUMN allowed_grant_types VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaigns ALTER COLUMN canvas_settings VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_events ALTER COLUMN properties VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_lead_event_log ALTER COLUMN metadata VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'audit_log ALTER COLUMN details VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'ip_addresses ALTER COLUMN ip_details VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ALTER COLUMN content VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ALTER COLUMN variant_settings VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats ALTER COLUMN tokens VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_actions ALTER COLUMN properties VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN label VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN custom_parameters VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN default_value VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN validation_message VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN help_message VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN properties VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'leads ALTER COLUMN internal VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'leads ALTER COLUMN social_cache VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_fields ALTER COLUMN properties VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_lists ALTER COLUMN filters VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'page_hits ALTER COLUMN browser_languages VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages ALTER COLUMN content VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages ALTER COLUMN variant_settings VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'points ALTER COLUMN properties VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_trigger_events ALTER COLUMN properties VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'reports ALTER COLUMN columns VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'reports ALTER COLUMN filters VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'reports ALTER COLUMN table_order VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'reports ALTER COLUMN graphs VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'roles ALTER COLUMN readable_permissions VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'users ALTER COLUMN preferences VARCHAR(MAX)');
    }
}
