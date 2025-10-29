<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Exception\ImportRowFailedException;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ImportDateValidationSubscriber implements EventSubscriberInterface
{
    /**
     * Cached mapping of date/datetime fields.
     *
     * Keys are field aliases, values are either 'date' or 'datetime'.
     *
     * @var array<string,'date'|'datetime'>|null
     */
    private ?array $dateFields = null;

    public function __construct(
        private FieldModel $fieldModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::IMPORT_ON_PROCESS => ['onImportProcess', 100],
        ];
    }

    /**
     * @throws ImportRowFailedException
     */
    public function onImportProcess(ImportProcessEvent $event): void
    {
        if (!in_array($event->import->getObject(), ['lead', 'company'], true)) {
            return;
        }
        foreach ($this->getDateFields() as $alias => $type) {
            $this->validateDateTimeField($event->import->getMatchedFields(), $event->rowData, $alias, $type);
        }
    }

    /**
     * Validate one field value if it's date/datetime.
     *
     * @param array<string, string>      $mappedData Matched fields
     * @param array<string, string|null> $rowData    Row data keyed by field alias
     * @param string                     $alias      Field alias
     * @param 'date'|'datetime'          $type       Field type
     *
     * @throws ImportRowFailedException
     */
    private function validateDateTimeField(array $mappedData, array $rowData, string $alias, string $type): void
    {
        $new_alias = array_search($alias, $mappedData, true);

        if (false === $new_alias) {
            return;
        }

        $value = trim((string) $rowData[$new_alias]);

        if ('' === $value) {
            return;
        }

        switch ($type) {
            case 'datetime':
                $this->createDate('Y-m-d H:i:s', $value, $alias, 'Only YYYY-MM-DD HH:MM:SS format is supported.');
                break;
            case 'date':
                $this->createDate('Y-m-d', $value, $alias, 'Only YYYY-MM-DD format is supported.');
                break;
        }
    }

    /**
     * Get all published date/datetime fields (cached).
     *
     * @return array<string,'date'|'datetime'> keyed by field alias
     */
    private function getDateFields(): array
    {
        if (null !== $this->dateFields) {
            return $this->dateFields;
        }

        $this->dateFields = [];
        $fields           = $this->fieldModel->getEntities([
            'filter' => [
                'force' => [
                    ['column' => 'f.isPublished', 'expr' => 'eq', 'value' => true],
                    ['column' => 'f.type', 'expr' => 'in', 'value' => ['date', 'datetime']],
                ],
            ],
            'hydration_mode' => 'HYDRATE_ARRAY',
            'result_cache'   => new ResultCacheOptions(LeadField::CACHE_NAMESPACE),
        ]);

        foreach ($fields as $field) {
            $this->dateFields[$field['alias']] = $field['type'];
        }

        return $this->dateFields;
    }

    private function createDate(string $format, string $value, string $alias, string $errorMessage): void
    {
        $dt = \DateTime::createFromFormat($format, $value);

        $errors = $dt ? \DateTime::getLastErrors() : null;
        if (!$dt || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            $message = sprintf(
                'Row skipped: invalid value "%s" for field "%s". %s',
                $value,
                $alias,
                $errorMessage
            );
            throw new ImportRowFailedException($message);
        }
    }
}
