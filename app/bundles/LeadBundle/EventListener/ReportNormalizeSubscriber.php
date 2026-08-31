<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Helper\CustomFieldValueHelper;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ReportNormalizeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LeadFieldRepository $leadFieldRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportDataEvent::class => ['onReportDisplay', 0],
        ];
    }

    public function onReportDisplay(ReportDataEvent $event): void
    {
        if (!$this->useContactOrCompanyColumn($event->getReport()->getColumns())) {
            return;
        }

        $fields = $this->leadFieldRepository->getFields();
        $rows   = $event->getData();
        foreach ($rows as $key => $row) {
            foreach ($row as $alias => $value) {
                if (isset($fields[$alias])) {
                    $type               = $fields[$alias]['type'] ?? null;
                    $rows[$key][$alias] = CustomFieldValueHelper::normalize($value, $type, $fields[$alias]['properties'] ?? []);
                    if ('boolean' === $type) {
                        $event->updateColumnType($alias, 'normalized_bool');
                    }
                }
            }
        }
        $event->setData($rows);
    }

    /**
     * @param array<string> $columns
     */
    private function useContactOrCompanyColumn(array $columns): bool
    {
        return array_any($columns, fn (string $column): bool => str_starts_with($column, 'l.') || str_starts_with($column, 'comp.'));
    }
}
