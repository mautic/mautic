<?php

namespace Mautic\InstallBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Mautic\LeadBundle\Field\SchemaDefinition;
use Mautic\LeadBundle\Model\FieldModel;

class DoctrineEventSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'postGenerateSchema',
        ];
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        $fieldGroups['leads']     = FieldModel::$coreFields;
        $fieldGroups['companies'] = FieldModel::$coreCompanyFields;

        foreach ($fieldGroups as $tableName => $fields) {
            $table = $schema->getTable($tableName);

            foreach ($fields as $alias => $field) {
                if (!$table->hasColumn($alias)) {
                    $type       = $field['type'] ?? 'text';
                    $definition = SchemaDefinition::getSchemaDefinition($alias, $type, !empty($field['unique']));
                    $table->addColumn($definition['name'], $definition['type'], $definition['options']);

                    if ('textarea' !== $type) {
                        $table->addIndex([$definition['name']], $definition['name'].'_search');
                    }
                }
            }

            if ('leads' === $tableName) {
                // Add an attribution index
                $table->addIndex(['attribution', 'attribution_date'], 'contact_attribution');
                //Add date added and country index
                $table->addIndex(['date_added', 'country'], 'date_added_country_index');
            } else {
                $table->addIndex(['companyname', 'companyemail'], 'company_filter');
                $table->addIndex(['companyname', 'companycity', 'companycountry', 'companystate'], 'company_match');
            }
        }
    }
}
