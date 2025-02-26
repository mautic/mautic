<?php

namespace Mautic\ReportBundle\Helper;

use Mautic\ReportBundle\Event\ColumnCollectEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ReportHelper
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function getName(): string
    {
        return 'report';
    }

    /**
     * @return string
     */
    public function getReportBuilderFieldType($type)
    {
        return match ($type) {
            'number' => 'int',
            'lookup', 'text', 'url', 'email', 'tel', 'region', 'country', 'locale' => 'string',
            default => $type,
        };
    }

    /**
     * Returns standard form fields such as id, name, publish_up, etc.
     *
     * @param string[] $removeColumns
     *
     * @return array<string,array<string,string>>
     */
    public function getStandardColumns(string $prefix, array $removeColumns = [], string $idLink = ''): array
    {
        $aliasPrefix = str_replace('.', '_', $prefix);
        $columns     = [
            $prefix.'id' => [
                'label' => 'mautic.core.id',
                'type'  => 'int',
                'link'  => $idLink,
                'alias' => "{$aliasPrefix}id",
            ],
            $prefix.'name' => [
                'label' => 'mautic.core.name',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}name",
            ],
            $prefix.'created_by_user' => [
                'label' => 'mautic.core.createdby',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}created_by_user",
            ],
            $prefix.'date_added' => [
                'label' => 'mautic.report.field.date_added',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}date_added",
            ],
            $prefix.'modified_by_user' => [
                'label' => 'mautic.report.field.modified_by_user',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}modified_by_user",
            ],
            $prefix.'date_modified' => [
                'label' => 'mautic.report.field.date_modified',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}date_modified",
            ],
            $prefix.'description' => [
                'label' => 'mautic.core.description',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}description",
            ],
            $prefix.'publish_up' => [
                'label' => 'mautic.report.field.publish_up',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}publish_up",
            ],
            $prefix.'publish_down' => [
                'label' => 'mautic.report.field.publish_down',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}publish_down",
            ],
            $prefix.'is_published' => [
                'label' => 'mautic.report.field.is_published',
                'type'  => 'bool',
                'alias' => "{$aliasPrefix}is_published",
            ],
        ];

        if (empty($idLink)) {
            unset($columns[$prefix.'id']['link']);
        }

        foreach ($removeColumns as $c) {
            if (isset($columns[$prefix.$c])) {
                unset($columns[$prefix.$c]);
            }
        }

        return $columns;
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, array<string, mixed>>
     */
    public function getMappedObjectColumns(string $object, array $properties = []): array
    {
        $event = new ColumnCollectEvent($object, $properties);
        $this->dispatcher->dispatch($event, ReportEvents::REPORT_ON_COLUMN_COLLECT);

        return array_map(
            function ($item) {
                if (isset($item['type'])) {
                    $item['type'] =  $this->getReportBuilderFieldType($item['type']);
                }

                return $item;
            },
            $event->getColumns()
        );
    }
}
