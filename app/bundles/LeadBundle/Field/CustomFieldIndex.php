<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\SchemaException as DoctrineSchemaException;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\LeadBundle\Entity\LeadField;
use Psr\Log\LoggerInterface;

class CustomFieldIndex
{
    public function __construct(
        private IndexSchemaHelper $indexSchemaHelper,
        private LoggerInterface $logger,
        private FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier,
    ) {
    }

    /**
     * Update the unique_identifier_search index and add an index for this field.
     *
     * @throws DriverException
     * @throws DoctrineSchemaException
     * @throws SchemaException
     */
    public function addIndexOnColumn(LeadField $leadField): void
    {
        try {
            /** @var IndexSchemaHelper $modifySchema */
            $modifySchema = $this->indexSchemaHelper->setName($leadField->getCustomFieldObject());

            $alias = $leadField->getAlias();

            $modifySchema->addIndex([$alias], $alias.'_search');
            $modifySchema->allowColumn($alias);

            $this->updateUniqueIdentifierIndex($leadField);

            $modifySchema->executeChanges();
        } catch (DriverException $e) {
            if (1069 === $e->getCode() /* ER_TOO_MANY_KEYS */) {
                $this->logger->warning($e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    /**
     * Updates the index for this field.
     *
     * @throws DriverException
     * @throws DoctrineSchemaException
     * @throws SchemaException
     */
    public function dropIndexOnColumn(LeadField $leadField): void
    {
        try {
            /** @var IndexSchemaHelper $modifySchema */
            $modifySchema = $this->indexSchemaHelper->setName($leadField->getCustomFieldObject());

            $alias = $leadField->getAlias();

            $modifySchema->dropIndex([$alias], $alias.'_search');
            $modifySchema->allowColumn($alias);

            $modifySchema->executeChanges();
        } catch (DriverException $e) {
            if (1069 === $e->getCode() /* ER_TOO_MANY_KEYS */) {
                $this->logger->warning($e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    /**
     * @throws SchemaException
     */
    public function isUpdatePending(LeadField $leadField): bool
    {
        $hasIndex = $this->hasIndex($leadField);

        if ($leadField->isIsIndex() !== $hasIndex) {
            return true;
        }

        if (!$this->hasMatchingUniqueIdentifierIndex($leadField)) {
            return true;
        }

        return false;
    }

    /**
     * @throws SchemaException
     */
    public function hasIndex(LeadField $leadField): bool
    {
        return $this->indexSchemaHelper->hasIndex($leadField);
    }

    public function hasMatchingUniqueIdentifierIndex(LeadField $leadField): bool
    {
        $uniqueIdentifierColumns = $this->getUniqueIdentifierIndexColumns($leadField->getObject());

        try {
            return $this->indexSchemaHelper->hasMatchingUniqueIdentifierIndex($leadField, $uniqueIdentifierColumns);
        } catch (DoctrineSchemaException) {
            // Return true only if there are no unique identifier fields but otherwise assume the index is missing
            return 0 === count($uniqueIdentifierColumns);
        }
    }

    /**
     * @throws DoctrineSchemaException
     * @throws SchemaException
     */
    public function updateUniqueIdentifierIndex(LeadField $leadField): void
    {
        if ($this->hasMatchingUniqueIdentifierIndex($leadField)) {
            return;
        }

        /** @var IndexSchemaHelper $modifySchema */
        $modifySchema = $this->indexSchemaHelper->setName($leadField->getCustomFieldObject());

        $indexColumns = $this->getUniqueIdentifierIndexColumns($leadField->getObject());
        if (!$indexColumns) {
            $this->dropIndexForUniqueIdentifiers($leadField);

            return;
        }

        $modifySchema->addIndex($indexColumns, 'unique_identifier_search');
        $modifySchema->executeChanges();
    }

    /**
     * @throws DoctrineSchemaException
     * @throws SchemaException
     */
    private function dropIndexForUniqueIdentifiers(LeadField $leadField): void
    {
        if (!$this->indexSchemaHelper->hasUniqueIdentifierIndex($leadField)) {
            return;
        }

        /** @var IndexSchemaHelper $modifySchema */
        $modifySchema = $this->indexSchemaHelper->setName($leadField->getCustomFieldObject());

        $indexColumns = $this->getUniqueIdentifierIndexColumns($leadField->getObject());
        $modifySchema->dropIndex($indexColumns, 'unique_identifier_search');

        $modifySchema->executeChanges();
    }

    /**
     * @return array<mixed>
     */
    private function getUniqueIdentifierIndexColumns(string $object = 'lead'): array
    {
        // Filters
        $filters = ['object' => $object];

        // Get list of current uniques
        $uniqueIdentifierFields = $this->fieldsWithUniqueIdentifier->getLiveFields($filters);

        // Always use email
        $indexColumns = array_keys($uniqueIdentifierFields);

        // Only use three to prevent max key length errors
        return array_slice($indexColumns, 0, 3);
    }
}
