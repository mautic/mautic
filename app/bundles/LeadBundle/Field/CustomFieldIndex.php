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
            /** @var \Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper $modifySchema */
            $modifySchema = $this->indexSchemaHelper->setName($leadField->getCustomFieldObject());

            $alias = $leadField->getAlias();

            $modifySchema->addIndex([$alias], $alias.'_search');
            $modifySchema->allowColumn($alias);

            if ($leadField->getIsUniqueIdentifier()) {
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
            if (1069 === $e->getCode() /* ER_TOO_MANY_KEYS */) {
                $this->logger->warning($e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
