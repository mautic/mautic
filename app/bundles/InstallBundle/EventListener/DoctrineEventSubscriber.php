<?php

namespace Mautic\InstallBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
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
                if (!$table->hasColumn($alias)) {
                    $type       = $field['type'] ?? 'text';
                    $definition = SchemaDefinition::getSchemaDefinition($alias, $type, !empty($field['unique']));
                    $table->addColumn($definition['name'], $definition['type'], $definition['options']);

                    if ('textarea' !== $type) {
                        self::addIndexIfMissing($table, [$definition['name']], $definition['name'].'_search');
                    }
                }
            }

            if ('leads' === $tableName) {
                // Add an attribution index
                self::addIndexIfMissing($table, ['attribution', 'attribution_date'], 'contact_attribution');
                // Add date added and country index
                self::addIndexIfMissing($table, ['date_added', 'country'], 'date_added_country_index');
            } else {
                self::addIndexIfMissing($table, ['companyname', 'companyemail'], 'company_filter');
                self::addIndexIfMissing($table, ['companyname', 'companycity', 'companycountry', 'companystate'], 'company_match');
            }
        }
    }

    /**
     * DBAL 4 throws IndexAlreadyExists when an index name is reused; DBAL 3 silently
     * replaced it. The definitions added here are identical on every pass, so skipping
     * an existing one preserves the previous outcome.
     *
     * @param string[] $columns
     */
    private static function addIndexIfMissing(Table $table, array $columns, string $name): void
    {
        if ($table->hasIndex($name)) {
            return;
        }

        $table->addIndex($columns, $name);
    }
}
