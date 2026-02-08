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

final class ImportUrlValidationSubscriber implements EventSubscriberInterface
{
    /**
     * Cached mapping of url/website fields.
     *
     * Keys are field aliases, values are either 'url' or 'website'.
     *
     * @var array<string,'url'|'website'>|null
     */
    private ?array $urlFields = null;

    public function __construct(
        private FieldModel $fieldModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::IMPORT_ON_PROCESS => ['onImportProcess', 210],
        ];
    }

    /**
     * Validate URL fields during CSV import (lead + company).
     *
     * @throws ImportRowFailedException
     */
    public function onImportProcess(ImportProcessEvent $event): void
    {
        if (!in_array($event->import->getObject(), ['lead', 'company'], true)) {
            return;
        }

        foreach ($this->getUrlFields() as $alias => $type) {
            $this->validateUrlField(
                $event->import->getMatchedFields(),
                $event->rowData,
                $alias,
                $type
            );
        }
    }

    /**
     * Validate one field value if it's url/website.
     *
     * @param array<string, string>      $mappedData Matched fields
     * @param array<string, string|null> $rowData    Row data keyed by field alias
     * @param string                     $alias      Field alias
     * @param 'url'|'website'            $type       Field type
     *
     * @throws ImportRowFailedException
     */
    private function validateUrlField(
        array $mappedData,
        array $rowData,
        string $alias,
        string $type,
    ): void {
        $new_alias = array_search($alias, $mappedData, true);

        if (false === $new_alias) {
            return;
        }

        $value = trim((string) $rowData[$new_alias]);

        if ('' === $value) {
            return;
        }

        if (in_array($type, ['url', 'website'], true)) {
            $lower = strtolower($value);

            if (str_starts_with($lower, 'data:')) {
                $message = sprintf(
                    'Row skipped: invalid value "%s" for field "%s". %s',
                    $value,
                    $alias,
                    'data: protocol is not allowed.'
                );
                throw new ImportRowFailedException($message);
            }
        }
    }

    /**
     * Get all published url/website fields (cached).
     *
     * @return array<string,'url'|'website'> keyed by field alias
     */
    private function getUrlFields(): array
    {
        if (null !== $this->urlFields) {
            return $this->urlFields;
        }

        $this->urlFields = [];

        $fields = $this->fieldModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true,
                    ],
                    [
                        'column' => 'f.type',
                        'expr'   => 'in',
                        'value'  => ['url', 'website'],
                    ],
                ],
            ],
            'hydration_mode' => 'HYDRATE_ARRAY',
            'result_cache'   => new ResultCacheOptions(LeadField::CACHE_NAMESPACE),
        ]);

        foreach ($fields as $field) {
            $this->urlFields[$field['alias']] = $field['type'];
        }

        return $this->urlFields;
    }
}
