<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace Mautic\LeadBundle\EventListener;


use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\CoreBundle\Factory\MauticFactory;

class DoctrineSubscriber implements \Doctrine\Common\EventSubscriber
{
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            ToolEvents::postGenerateSchema
        );
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args)
    {
        $schema = $args->getSchema();

        try {
            if (!$schema->hasTable(MAUTIC_TABLE_PREFIX.'lead_fields')) {
                return;
            }

            $table = $schema->getTable(MAUTIC_TABLE_PREFIX.'leads');

            //get a list of fields
            $fields = $this->factory->getModel('lead.field')->getRepository()->getFieldAliases();

            // Compile which ones are unique identifiers
            // Email will always be included first
            $uniqueFields = array('email' => 'email');

            foreach ($fields as $f) {
                if ($f['is_unique'] && $f['alias'] != 'email') {
                    $uniqueFields[$f['alias']] = $f['alias'];
                }

                if (in_array($f['alias'], array('country', 'email')) || $f['is_unique']) {
                    $table->addColumn($f['alias'], 'string', array('notnull' => false));
                    $table->addIndex(array($f['alias']), MAUTIC_TABLE_PREFIX.'lead_field'.$f['alias'].'_search');
                } elseif ($f['is_unique']) {
                    $table->addColumn($f['alias'], 'string', array('notnull' => false));
                } else {
                    $table->addColumn($f['alias'], 'text', array('notnull' => false));
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

        } catch (\Exception $e) {
            //table doesn't exist or something bad happened so oh well
            error_log($e->getMessage());
        }
    }
}