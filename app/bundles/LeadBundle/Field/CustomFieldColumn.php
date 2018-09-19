<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Field;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\LeadBundle\Entity\LeadField;
use Monolog\Logger;
use Symfony\Component\Translation\TranslatorInterface;

class CustomFieldColumn
{
    /**
     * @var IndexSchemaHelper
     */
    private $indexSchemaHelper;

    /**
     * @var ColumnSchemaHelper
     */
    private $columnSchemaHelper;

    /**
     * @var SchemaDefinition
     */
    private $schemaDefinition;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FieldsWithUniqueIdentifier
     */
    private $fieldsWithUniqueIdentifier;

    /**
     * @var LeadFieldSaver
     */
    private $leadFieldSaver;

    public function __construct(
        IndexSchemaHelper $indexSchemaHelper,
        ColumnSchemaHelper $columnSchemaHelper,
        SchemaDefinition $schemaDefinition,
        Logger $logger,
        TranslatorInterface $translator,
        FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier,
        LeadFieldSaver $leadFieldSaver
    ) {
        $this->indexSchemaHelper          = $indexSchemaHelper;
        $this->columnSchemaHelper         = $columnSchemaHelper;
        $this->schemaDefinition           = $schemaDefinition;
        $this->logger                     = $logger;
        $this->translator                 = $translator;
        $this->fieldsWithUniqueIdentifier = $fieldsWithUniqueIdentifier;
        $this->leadFieldSaver             = $leadFieldSaver;
    }

    /**
     * @param string $object - 'leads' or 'companies'
     *
     * @throws DBALException
     * @throws DriverException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function createLeadColumn(LeadField $entity, $object)
    {
        // Create the field as its own column in the leads table.
        /** @var ColumnSchemaHelper $leadsSchema */
        $leadsSchema = $this->columnSchemaHelper->setName($object);
        $isUnique    = $entity->getIsUniqueIdentifier();
        $alias       = $entity->getAlias();

        // We do not need to do anything if the column already exists
        if ($leadsSchema->checkColumnExists($alias)) {
            return;
        }

        $schemaDefinition = $this->schemaDefinition::getSchemaDefinition($alias, $entity->getType(), $isUnique);

        $leadsSchema->addColumn($schemaDefinition);

        try {
            $leadsSchema->executeChanges();
        } catch (DriverException $e) {
            $this->logger->addWarning($e->getMessage());

            if (1118 === $e->getErrorCode() /* ER_TOO_BIG_ROWSIZE */) {
                throw new DBALException($this->translator->trans('mautic.core.error.max.field'));
            }

            throw $e;
        }

        // If this is a new contact field, and it was successfully added to the contacts table, save it
        if (true === $entity->isNew()) {
            $this->leadFieldSaver->saveLeadFieldEntity($entity, true);
        }

        // Update the unique_identifier_search index and add an index for this field
        /** @var \Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper $modifySchema */
        $modifySchema = $this->indexSchemaHelper->setName($object);

        if ('string' !== $schemaDefinition['type']) {
            return;
        }

        try {
            $modifySchema->addIndex([$alias], $alias.'_search');
            $modifySchema->allowColumn($alias);

            if ($isUnique) {
                // Get list of current uniques
                $uniqueIdentifierFields = $this->fieldsWithUniqueIdentifier->getFieldsWithUniqueIdentifier();

                // Always use email
                $indexColumns   = ['email'];
                $indexColumns   = array_merge($indexColumns, array_keys($uniqueIdentifierFields));
                $indexColumns[] = $alias;

                // Only use three to prevent max key length errors
                $indexColumns = array_slice($indexColumns, 0, 3);
                $modifySchema->addIndex($indexColumns, 'unique_identifier_search');
            }

            $modifySchema->executeChanges();
        } catch (DriverException $e) {
            if (1069 === $e->getErrorCode() /* ER_TOO_MANY_KEYS */) {
                $this->logger->addWarning($e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
