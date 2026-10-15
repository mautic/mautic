<?php

namespace Mautic\LeadBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\LeadBundle\Field\SchemaDefinition;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(ToolEvents::postGenerateSchema)]
final readonly class DoctrineSubscriber
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.mautic')]
        private Logger $logger,
    ) {
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        try {
            if (!$schema->hasTable(MAUTIC_TABLE_PREFIX.'lead_fields')) {
                return;
            }

            $objects = [
                'lead'    => 'leads',
                'company' => 'companies',
            ];

            foreach ($objects as $object => $tableName) {
                $table = $schema->getTable(MAUTIC_TABLE_PREFIX.$tableName);

                // get a list of fields
                $fields = $args->getEntityManager()->getConnection()->createQueryBuilder()
                    ->select('f.alias, f.is_unique_identifer as is_unique, f.is_index, f.type, f.object')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
                    ->where("f.object = '{$object}'")
                    ->orderBy('f.field_order', 'ASC')
                    ->executeQuery()
                    ->fetchAllAssociative();

                // Compile which ones are unique identifiers
                $uniqueFields = [];
                foreach ($fields as $field) {
                    if ($field['is_unique']) {
                        $uniqueFields[$field['alias']] = $field['alias'];
                    }
                    $columnDef = SchemaDefinition::getSchemaDefinition($field['alias'], $field['type'], !empty($field['is_unique']));

                    if (!$table->hasColumn($field['alias'])) {
                        $table->addColumn($columnDef['name'], $columnDef['type'], $columnDef['options']);
                    }

                    if ((!empty($field['is_unique']) || !empty($field['is_index'])) && !$table->hasIndex(MAUTIC_TABLE_PREFIX.$field['alias'].'_search')) {
                        $table->addIndex([$columnDef['name']], MAUTIC_TABLE_PREFIX.$field['alias'].'_search');
                    }
                }

                // Only allow indexes for string types
                $columns = $table->getColumns();
                /** @var \Doctrine\DBAL\Schema\Column $column */
                foreach ($columns as $column) {
                    $type = $column->getType();
                    $name = $column->getName();

                    if (!$type instanceof StringType) {
                        unset($uniqueFields[$name]);
                    }
                }

                if (count($uniqueFields) > 1) {
                    // Only use three to prevent max key length errors
                    asort($uniqueFields);
                    $uniqueFields = array_slice($uniqueFields, 0, 3);
                    if (!$table->hasIndex(MAUTIC_TABLE_PREFIX.$object.'_unique_identifier_search')) {
                        $table->addIndex($uniqueFields, MAUTIC_TABLE_PREFIX.$object.'_unique_identifier_search');
                    }
                }

                $indexes = 'lead' === $object ? [
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
        } catch (\Exception $e) {
            if (defined('MAUTIC_INSTALLER')) {
                return;
            }
            // table doesn't exist or something bad happened so oh well
            $this->logger->error('SCHEMA ERROR: '.$e->getMessage());
        }
    }
}
