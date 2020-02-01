<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field;

use Doctrine\DBAL\Exception\DriverException;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\LeadBundle\Entity\LeadField;
use Psr\Log\LoggerInterface;

class CustomFieldIndex
{
    public function __construct(
        private IndexSchemaHelper $indexSchemaHelper,
        private LoggerInterface $logger,
        private FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier
    ) {
    }

    /**
     * Update the unique_identifier_search index and add an index for this field.
     *
     * @throws DriverException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
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
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
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
     * @throws \Mautic\CoreBundle\Exception\SchemaException
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
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function hasIndex(LeadField $leadField): bool
    {
        return $this->indexSchemaHelper->hasIndex($leadField);
    }

    public function hasMatchingUniqueIdentifierIndex(LeadField $leadField)
    {
        $hasIndex = $this->indexSchemaHelper->hasUniqueIdentifierIndex($leadField);
        $isUniqueIdentifier = $leadField->getIsUniqueIdentifier();

        if ($isUniqueIdentifier && !$hasIndex)
        {
            return false;
        }

        if (!$isUniqueIdentifier && $hasIndex)
        {
            return false;
        }

        $uniqueIdentifierColumns = $this->getUniqueIdentifierIndexColumns();
        if ($uniqueIdentifierColumns && !$hasIndex) {
            return false;
        }

        if (!$uniqueIdentifierColumns && $hasIndex) {
            return false;
        }

        if (!$uniqueIdentifierColumns && !$hasIndex) {
            return true;
        }

        return $this->indexSchemaHelper->hasMatchingUniqueIdentifierIndex($leadField, $uniqueIdentifierColumns);
    }

    /**
     * @param LeadField $leadField
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function updateUniqueIdentifierIndex(LeadField $leadField)
    {
        if ($this->hasMatchingUniqueIdentifierIndex($leadField)) {
            return;
        }

        /** @var \Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper $modifySchema */
        $modifySchema = $this->indexSchemaHelper->setName($leadField->getCustomFieldObject());

        $indexColumns = $this->getUniqueIdentifierIndexColumns();
        if (!$indexColumns) {
            $this->dropIndexForUniqueIdentifiers($leadField);

            return;
        }

        $modifySchema->addIndex($indexColumns, 'unique_identifier_search');
        $modifySchema->executeChanges();
    }

    /**
     * @param LeadField $leadField
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    private function dropIndexForUniqueIdentifiers(LeadField $leadField)
    {
        if (!$this->indexSchemaHelper->hasUniqueIdentifierIndex($leadField)) {
            return;
        }

        /** @var IndexSchemaHelper $modifySchema */
        $modifySchema = $this->indexSchemaHelper->setName($leadField->getCustomFieldObject());

        $indexColumns = $this->getUniqueIdentifierIndexColumns();
        $modifySchema->dropIndex($indexColumns, 'unique_identifier_search');

        $modifySchema->executeChanges();
    }

    /**
     * @return array
     */
    private function getUniqueIdentifierIndexColumns()
    {
        // Get list of current uniques
        $uniqueIdentifierFields = $this->fieldsWithUniqueIdentifier->getFieldsWithUniqueIdentifier();

        // Always use email
        $indexColumns = array_keys($uniqueIdentifierFields);

        // Only use three to prevent max key length errors
        return array_slice($indexColumns, 0, 3);
    }
}
