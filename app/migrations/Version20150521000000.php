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
        $q->update(MAUTIC_TABLE_PREFIX . 'form_fields')
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
            ->from(MAUTIC_TABLE_PREFIX . 'lead_fields', 'f')
            ->execute()
            ->fetchAll();
        $fields = array();
        foreach ($results as $field) {
            $fields[$field['id']] = $field['alias'];
        }
        unset($results);

        // Get all the actions that are lead.create
        $q       = $this->connection->createQueryBuilder();
        $actions = $q->select('a.id, a.properties, a.form_id, f.created_by, f.created_by_user')
            ->from(MAUTIC_TABLE_PREFIX . 'form_actions', 'a')
            ->join('a', MAUTIC_TABLE_PREFIX . 'forms', 'f', 'a.form_id = f.id')
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
                    $point->setName('Migrated: Form #' . $action['form_id']);
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
            if (!isset($fields[$leadFieldId]))
                continue;

            $q = $this->connection->createQueryBuilder();
            $q->update(MAUTIC_TABLE_PREFIX . 'form_fields')
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
            $q->delete(MAUTIC_TABLE_PREFIX . 'form_actions')
                ->where(
                    $q->expr()->in('id', $deleteActions)
                )
                ->execute();
        }
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD container_attr VARCHAR(255) DEFAULT NULL, ADD lead_field VARCHAR(255) DEFAULT NULL, ADD save_result TINYINT(1) DEFAULT NULL, CHANGE `label` `label` LONGTEXT NOT NULL, CHANGE default_value default_value LONGTEXT DEFAULT NULL, CHANGE validation_message validation_message LONGTEXT DEFAULT NULL, CHANGE help_message help_message LONGTEXT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD container_attr VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD lead_field VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD save_result BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER label TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER default_value TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER validation_message TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER help_message TYPE TEXT');
    }

    /**
     * @param Schema $schema
     */
    public function mssqlUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD container_attr NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD lead_field NVARCHAR(255)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ADD save_result BIT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN label VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN custom_parameters VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN default_value VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN validation_message VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN help_message VARCHAR(MAX)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_fields ALTER COLUMN properties VARCHAR(MAX)');
    }
}
