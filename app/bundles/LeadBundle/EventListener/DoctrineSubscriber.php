<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\LeadBundle\Model\FieldModel;
use Monolog\Logger;

/**
 * Class DoctrineSubscriber.
 */
class DoctrineSubscriber implements \Doctrine\Common\EventSubscriber
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * DoctrineSubscriber constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            ToolEvents::postGenerateSchema,
        ];
    }

    /**
     * @param GenerateSchemaEventArgs $args
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $args)
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

                //get a list of fields
                $fields = $args->getEntityManager()->getConnection()->createQueryBuilder()
                    ->select('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
                    ->where("f.object = '$object'")
                    ->orderBy('f.field_order', 'ASC')
                    ->execute()->fetchAll();

                // Compile which ones are unique identifiers
                // Email will always be included first
                $uniqueFields = ('lead' === $object) ? ['email' => 'email'] : ['companyemail' => 'companyemail'];
                foreach ($fields as $f) {
                    if ($f['is_unique'] && $f['alias'] != 'email') {
                        $uniqueFields[$f['alias']] = $f['alias'];
                    }
                    $columnDef = FieldModel::getSchemaDefinition($f['alias'], $f['type'], !empty($f['is_unique']));

                    if (!$table->hasColumn($f['alias'])) {
                        $table->addColumn($columnDef['name'], $columnDef['type'], $columnDef['options']);
                    }

                    if ('text' != $columnDef['type']) {
                        $table->addIndex([$columnDef['name']], MAUTIC_TABLE_PREFIX.$f['alias'].'_search');
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
                    } elseif (isset($uniqueFields[$name])) {
                        $uniqueFields[$name] = $uniqueFields[$name];
                    }
                }

                if (count($uniqueFields) > 1) {
                    // Only use three to prevent max key length errors
                    $uniqueFields = array_slice($uniqueFields, 0, 3);
                    $table->addIndex($uniqueFields, MAUTIC_TABLE_PREFIX.'unique_identifier_search');
                }

                switch ($object) {
                    case 'lead':
                        $table->addIndex(['attribution', 'attribution_date'], MAUTIC_TABLE_PREFIX.'contact_attribution');
                        break;
                    case 'company':
                        $table->addIndex(['companyname', 'companyemail'], MAUTIC_TABLE_PREFIX.'company_filter');
                        $table->addIndex(['companyname', 'companycity', 'companycountry', 'companystate'], MAUTIC_TABLE_PREFIX.'company_match');
                        break;
                }
            }
        } catch (\Exception $e) {
            //table doesn't exist or something bad happened so oh well
            $this->logger->addError('SCHEMA ERROR: '.$e->getMessage());
        }
    }
}
