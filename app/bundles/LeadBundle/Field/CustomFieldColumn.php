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
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Dispatcher\FieldColumnDispatcher;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Monolog\Logger;
use Symfony\Component\Translation\TranslatorInterface;

class CustomFieldColumn
{
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
     * @var LeadFieldSaver
     */
    private $leadFieldSaver;

    /**
     * @var CustomFieldIndex
     */
    private $customFieldIndex;

    /**
     * @var FieldColumnDispatcher
     */
    private $fieldColumnDispatcher;

    public function __construct(
        ColumnSchemaHelper $columnSchemaHelper,
        SchemaDefinition $schemaDefinition,
        Logger $logger,
        TranslatorInterface $translator,
        LeadFieldSaver $leadFieldSaver,
        CustomFieldIndex $customFieldIndex,
        FieldColumnDispatcher $fieldColumnDispatcher
    ) {
        $this->columnSchemaHelper    = $columnSchemaHelper;
        $this->schemaDefinition      = $schemaDefinition;
        $this->logger                = $logger;
        $this->translator            = $translator;
        $this->leadFieldSaver        = $leadFieldSaver;
        $this->customFieldIndex      = $customFieldIndex;
        $this->fieldColumnDispatcher = $fieldColumnDispatcher;
    }

    /**
     * @throws DBALException
     * @throws DriverException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function createLeadColumn(LeadField $leadField)
    {
        // Create the field as its own column in the leads table.
        /** @var ColumnSchemaHelper $leadsSchema */
        $leadsSchema = $this->columnSchemaHelper->setName($leadField->getCustomFieldObject());
        $alias       = $leadField->getAlias();

        // We do not need to do anything if the column already exists
        if ($leadsSchema->checkColumnExists($alias)) {
            return;
        }

        try {
            $this->fieldColumnDispatcher->dispatchPreAddColumnEvent($leadField);
        } catch (NoListenerException $e) {
        } catch (AbortColumnCreateException $e) {
            return;
        }

        $schemaDefinition = $this->schemaDefinition::getSchemaDefinition($alias, $leadField->getType(), $leadField->getIsUniqueIdentifier());

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
        if (true === $leadField->isNew()) {
            $this->leadFieldSaver->saveLeadFieldEntity($leadField, true);
        }

        if ('string' === $schemaDefinition['type']) {
            $this->customFieldIndex->addIndexOnColumn($leadField);
        }
    }
}
