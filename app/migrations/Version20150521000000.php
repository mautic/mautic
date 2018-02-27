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
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\FormBundle\Entity\Action;
use Mautic\PageBundle\Entity\Redirect;

/**
 * Migrate 1.0.5 to 1.0.6.
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
        $table = $schema->getTable($this->prefix.'form_fields');
        if ($table->hasColumn('lead_field')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $em = $this->factory->getEntityManager();

        // Migrate asset download messages to form message
        $q = $this->connection->createQueryBuilder();
        $q->select('fa.properties, fa.form_id')
            ->from(MAUTIC_TABLE_PREFIX.'form_actions', 'fa')
            ->where(
                $q->expr()->eq('fa.type', $q->expr()->literal('asset.download'))
            );
        $results = $q->execute()->fetchAll();

        foreach ($results as $r) {
            $properties = unserialize($r['properties']);
            if (is_array($properties) && !empty($properties['message'])) {
                $this->connection->update(MAUTIC_TABLE_PREFIX.'forms',
                    [
                        'post_action'          => 'message',
                        'post_action_property' => $properties['message'],
                    ],
                    [
                        'id' => $r['form_id'],
                    ]
                );
            }
        }

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
        $fields = [];
        foreach ($results as $field) {
            $fields[$field['id']] = $field['alias'];
        }
        unset($results);

        // Get all the actions that are lead.create
        $q       = $this->connection->createQueryBuilder();
        $actions = $q->select('a.id, a.properties, a.form_id, a.action_order, f.created_by, f.created_by_user')
            ->from(MAUTIC_TABLE_PREFIX.'form_actions', 'a')
            ->join('a', MAUTIC_TABLE_PREFIX.'forms', 'f', 'a.form_id = f.id')
            ->where(
                $q->expr()->eq('a.type', $q->expr()->literal('lead.create'))
            )
            ->execute()
            ->fetchAll();

        $formFieldMatches = [];
        $actionEntities   = [];
        $deleteActions    = [];
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
                    $formAction = new Action();
                    $formAction->setType('lead.pointschange');
                    $formAction->setName('Migrated');
                    $formAction->setDescription('<p>Migrated during 1.1.0 upgrade. The Create/Update Lead form submit action is now obsolete.</p>');
                    $formAction->setForm($em->getReference('MauticFormBundle:Form', $action['form_id']));
                    $formAction->setOrder($action['action_order']);
                    $formAction->setProperties(
                        [
                            'operator' => 'plus',
                            'points'   => $properties['points'],
                        ]
                    );
                    $actionEntities[] = $formAction;
                    unset($formAction);
                }

                $deleteActions[] = $action['id'];
            } catch (\Exception $e) {
            }
        }

        if (!empty($actionEntities)) {
            $this->factory->getModel('point')->getRepository()->saveEntities($actionEntities);
            $em->clear('Mautic\FormBundle\Entity\Action');
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
            $em->clear('Mautic\FormBundle\Entity\Form');
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

        $q      = $this->connection->createQueryBuilder();
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
        $redirectEntities = [];
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
            $em->clear('Mautic\PageBundle\Entity\Redirect');
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
            $templateEmails = [];
            foreach ($results as $email) {
                $templateEmails[$email['email_id']] = $email;
            }
            $templateEmailIds = array_keys($templateEmails);
            /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
            $emailModel = $this->factory->getModel('email');

            $emails = $emailModel->getEntities(
                [
                    'iterator_mode' => true,
                    'filter'        => [
                        'force' => [
                            [
                                'column' => 'e.id',
                                'expr'   => 'in',
                                'value'  => $templateEmailIds,
                            ],
                        ],
                    ],
                ]
            );

            $persistListEmails = $persistTemplateEmails = $variants = [];

            // Clone since the ID may be in a bunch of serialized properties then convert new to a list based email
            while (($row = $emails->next()) !== false) {
                /** @var \Mautic\EmailBundle\Entity\Email $templateEmail */
                $templateEmail = reset($row);
                $id            = $templateEmail->getId();
                $listEmail     = clone $templateEmail;
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
            $persistVariants   = [];
            $processedVariants = [];

            foreach ($variants as $templateEmailId) {
                if ($persistTemplateEmails[$templateEmailId]->isVariant(true)) {
                    // A variant of another so get parent
                    $templateParent = $persistTemplateEmails[$templateEmailId]->getVariantParent();
                } else {
                    $templateParent = $persistTemplateEmails[$templateEmailId];
                }

                if (in_array($templateParent->getId(), $processedVariants)) {
                    continue;
                }

                $processedVariants[] = $templateParent->getId();

                // Get the children to clone each one
                $children = $templateParent->getVariantChildren();

                // If the parent is not already cloned, then do so
                /* @var \Mautic\EmailBundle\Entity\Email $listParent */
                if (!isset($persistListEmails[$templateParent->getId()])) {
                    $listParent = clone $templateParent;
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

                    $listChild = clone $templateChild;
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
                $q->update(MAUTIC_TABLE_PREFIX.'page_hits')
                    ->set('email_id', $listEmail->getId())
                    ->where('lead_id IN '.sprintf('(%s)', $sq->getSql()).' AND email_id = '.$templateId)
                    ->execute();

                // Update download hits
                $q = $this->connection->createQueryBuilder();
                $q->update(MAUTIC_TABLE_PREFIX.'asset_downloads')
                    ->set('email_id', $listEmail->getId())
                    ->where('lead_id IN '.sprintf('(%s)', $sq->getSql()).' AND email_id = '.$templateId)
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
    public function up(Schema $schema)
    {
        // Ensure the render_style column exists to prevent ORM errors
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD COLUMN render_style bool DEFAULT NULL');

        $this->addSql(
            'CREATE TABLE IF NOT EXISTS '.$this->prefix.'email_assets_xref (email_id INT NOT NULL, asset_id INT NOT NULL, INDEX '.$this->generatePropertyName('email_assets_xref', 'idx', ['email_id']).'  (email_id), INDEX '.$this->generatePropertyName('email_assets_xref', 'idx', ['asset_id']).'  (asset_id), PRIMARY KEY(email_id, asset_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE '.$this->prefix.'email_assets_xref ADD CONSTRAINT '.$this->generatePropertyName('email_assets_xref', 'fk', ['email_id']).'  FOREIGN KEY (email_id) REFERENCES '.$this->prefix.'emails (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'email_assets_xref ADD CONSTRAINT '.$this->generatePropertyName('email_assets_xref', 'fk', ['asset_id']).'  FOREIGN KEY (asset_id) REFERENCES '.$this->prefix.'assets (id)');

        $this->addSql('ALTER TABLE '.$this->prefix.'assets ADD size INT DEFAULT NULL');

        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_lead_event_log DROP FOREIGN KEY '.$this->findPropertyName('campaign_lead_event_log', 'fk', 'F639F774'));
        $this->addSql(
            'ALTER TABLE '.$this->prefix.'campaign_lead_event_log ADD CONSTRAINT '.$this->generatePropertyName('campaign_lead_event_log', 'fk', ['campaign_id']).'  FOREIGN KEY (campaign_id) REFERENCES '.$this->prefix.'campaigns (id)'
        );

        $this->addSql(
            'ALTER TABLE '.$this->prefix.'emails ADD name VARCHAR(255) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD from_address VARCHAR(255) DEFAULT NULL, ADD from_name VARCHAR(255) DEFAULT NULL, ADD reply_to_address VARCHAR(255) DEFAULT NULL, ADD bcc_address VARCHAR(255) DEFAULT NULL, ADD email_type VARCHAR(255) DEFAULT NULL, CHANGE subject subject LONGTEXT DEFAULT NULL, CHANGE template template VARCHAR(255) DEFAULT NULL'
        );
        $this->addSql('ALTER TABLE '.$this->prefix.'emails CHANGE content_mode content_mode VARCHAR(255) DEFAULT NULL');

        $this->addSql(
            'ALTER TABLE '.$this->prefix.'email_stats ADD copy LONGTEXT DEFAULT NULL, ADD open_count INT DEFAULT NULL, ADD last_opened DATETIME DEFAULT NULL, ADD open_details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE email_id email_id INT DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE '.$this->prefix.'form_fields ADD container_attr VARCHAR(255) DEFAULT NULL, ADD lead_field VARCHAR(255) DEFAULT NULL, ADD save_result TINYINT(1) DEFAULT NULL, CHANGE `label` `label` LONGTEXT NOT NULL, CHANGE default_value default_value LONGTEXT DEFAULT NULL, CHANGE validation_message validation_message LONGTEXT DEFAULT NULL, CHANGE help_message help_message LONGTEXT DEFAULT NULL'
        );

        $this->addSql('ALTER TABLE '.$this->prefix.'pages CHANGE template template VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'pages CHANGE content_mode content_mode VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE '.$this->prefix.'page_redirects ADD email_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE '.$this->prefix.'page_redirects ADD CONSTRAINT '.$this->generatePropertyName('page_redirects', 'fk', ['email_id']).' FOREIGN KEY (email_id) REFERENCES '.$this->prefix.'emails (id) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('page_redirects', 'idx', ['email_id']).' ON '.$this->prefix.'page_redirects (email_id)');

        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD form_type VARCHAR(255) DEFAULT NULL');

        $this->addSql('CREATE TABLE IF NOT EXISTS '.$this->prefix.'campaign_form_xref (campaign_id INT NOT NULL, form_id INT NOT NULL, INDEX '.$this->generatePropertyName('campaign_form_xref', 'idx', ['campaign_id']).' (campaign_id), INDEX '.$this->generatePropertyName('campaign_form_xref', 'idx', ['form_id']).' (form_id), PRIMARY KEY(campaign_id, form_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_form_xref ADD CONSTRAINT '.$this->generatePropertyName('campaign_form_xref', 'fk', ['campaign_id']).' FOREIGN KEY (campaign_id) REFERENCES '.$this->prefix.'campaigns (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_form_xref ADD CONSTRAINT '.$this->generatePropertyName('campaign_form_xref', 'fk', ['form_id']).' FOREIGN KEY (form_id) REFERENCES '.$this->prefix.'forms (id) ON DELETE CASCADE');
    }
}
