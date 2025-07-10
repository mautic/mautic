<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Helper\CustomFieldValueHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportNormalizeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FieldModel $fieldModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_DISPLAY => ['onReportDisplay', 0],
        ];
    }

    public function onReportDisplay(ReportDataEvent $event): void
    {
        if (!$this->useContactOrCompanyColumn($event->getReport()->getColumns())) {
            return;
        }

        $fields = $this->fieldModel->getRepository()->getFields();
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
    protected function useContactOrCompanyColumn(array $columns): bool
    {
        foreach ($columns as $column) {
            if (str_starts_with($column, 'l.') || str_starts_with($column, 'comp.')) {
                return true;
            }
        }

        return false;
    }
}
