<?php

namespace Mautic\InstallBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\LeadBundle\Field\SchemaDefinition;
use Mautic\LeadBundle\Model\FieldModel;

#[AsDoctrineListener(ToolEvents::postGenerateSchema)]
final class DoctrineEventSubscriber
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $fieldGroups = [
            'leads'     => FieldModel::$coreFields,
            'companies' => FieldModel::$coreCompanyFields,
        ];

        foreach ($fieldGroups as $tableName => $fields) {
            $fullTableName = MAUTIC_TABLE_PREFIX.$tableName;
            if (!$args->getSchema()->hasTable($fullTableName)) {
                // Ignore during plugin installations as not all tables are present in the schema.
                continue;
            }
            $table = $args->getSchema()->getTable($fullTableName);

            foreach ($fields as $alias => $field) {
                $type          = $field['type'] ?? 'text';
                $columnMissing = !$table->hasColumn($alias);
                if ($columnMissing) {
                    $definition = SchemaDefinition::getSchemaDefinition($alias, $type, !empty($field['unique']));
                    $table->addColumn($definition['name'], $definition['type'], $definition['options']);
                }

                $indexName = MAUTIC_TABLE_PREFIX.$alias.'_search';
                if ('textarea' !== $type && ($columnMissing || !empty($field['unique'])) && !$table->hasIndex($indexName)) {
                    $table->addIndex([$alias], $indexName);
                }
            }

            $indexes = 'leads' === $tableName ? [
                'contact_attribution'      => ['attribution', 'attribution_date'],
                'date_added_country_index' => ['date_added', 'country'],
            ] : [
                'company_filter' => ['companyname', 'companyemail'],
                'company_match'  => ['companyname', 'companycity', 'companycountry', 'companystate'],
            ];
            foreach ($indexes as $indexName => $columns) {
                if (!$table->hasIndex(MAUTIC_TABLE_PREFIX.$indexName)) {
                    $table->addIndex($columns, MAUTIC_TABLE_PREFIX.$indexName);
                }
            }
        }

        // Add MySQL missing functions + operators to PostgreSQL
        if ($args->getEntityManager()->getConnection() instanceof Connection) {
            // only run when real connetion exists
            $this->postgreSqlMySqlCompact($args->getEntityManager()->getConnection());
        }
    }

    public function postgreSqlMySqlCompact(Connection $conn): void
    {
        if (!DatabasePlatform::isPostgreSQL($conn->getDatabasePlatform())) {
            return;
        }

        // Functions and operator helpers
        foreach (PostgreSqlCompatibilityFunctions::getSql() as $sql) {
            $conn->executeStatement($sql);
        }

        // Operators – DROP IF EXISTS + CREATE (NO CREATE OR REPLACE OPTION)
        $operators = [
            '= (boolean, integer)'  => ['left' => 'boolean', 'right' => 'integer', 'proc' => 'pg_bool_eq_int', 'comm' => '=', 'neg' => '<>'],
            '= (integer, boolean)'  => ['left' => 'integer', 'right' => 'boolean', 'proc' => 'pg_int_eq_bool', 'comm' => '=', 'neg' => '<>'],
            '<> (boolean, integer)' => ['left' => 'boolean', 'right' => 'integer', 'proc' => 'pg_bool_neq_int', 'comm' => '<>', 'neg' => '='],
            '<> (integer, boolean)' => ['left' => 'integer', 'right' => 'boolean', 'proc' => 'pg_int_neq_bool', 'comm' => '<>', 'neg' => '='],
        ];

        foreach ($operators as $signature => $def) {
            $conn->executeStatement("DROP OPERATOR IF EXISTS {$signature}");
            $conn->executeStatement("
                CREATE OPERATOR {$def['comm']} (
                    PROCEDURE = {$def['proc']},
                    LEFTARG   = {$def['left']},
                    RIGHTARG  = {$def['right']},
                    COMMUTATOR = {$def['comm']},
                    NEGATOR    = {$def['neg']}
                    )");
        }
    }
}
